<?php

namespace Drupal\libcal;

use Drupal\Core\Datetime\DrupalDateTime;
use GuzzleHttp\Exception\RequestException;
use Masterminds\HTML5\Exception;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Class EventDownloadService.
 */
class EventDownloadService implements EventDownloadServiceInterface
{

    /**
     * Constructs a new EventDownloadService object.
     */
    public function __construct()
    {

    }

    public function postAccessToken($config)
    {
        try {
            $response = \Drupal::httpClient()->post($config->get('host')."/1.1/oauth/token", [
                'json' => [
                    'client_id' => $config->get("client_id"),
                    'client_secret' => $config->get("client_secret"),
                    'grant_type' => "client_credentials",
                ]
            ]);
            return json_decode((string)$response->getBody());
        } catch (RequestException $e) {
            print_r($e->getMessage());
            return null;
        }
    }

    public function get($params, $config)
    {
        if (empty($params)) {
            throw new \Exception("keyword must be valid");
        }

        $access_token = $this->postAccessToken($config)->access_token;
        $client = \Drupal::httpClient();
        $headers = [
            'Authorization' => 'Bearer ' . $access_token,
            'Accept' => 'application/json',
        ];
        try {
            $host = $config->get('host');
            $response = $client->get($host."/1.1/$params", [
                'headers' => $headers
            ]);
            return json_decode((string)$response->getBody());
        } catch (RequestException $e) {
            print_log($e->getMessage());
            return null;
        }
    }

    /**
     * @param array $events
     */
    public function libcalEventToNode(array $events)
    {
        // Yes, we loop over the events twice.
        // But doing it this way reduces database calls.

        // First loop collects all event categories and ensures they exist
        $categories = [];
        foreach ($events as $event) {
            foreach ($event->category as $c) {
                $categories[] = $c;
            }
        }

        // Get only unique values from categories list
        // array_unique() doesn't work on objects, only scalars
        // Elegant solution is to convert them to strings, get unique values, then convert them back
        $jsonStrings = array_unique(array_map('json_encode', $categories));
        // Convert JSON strings back to objects
        $categories = array_map('json_decode', $jsonStrings);

        foreach ($categories as $category) {
            $tid = $category->id;
            $name = $category->name;

            if (!$this->queryEventCategoryExists($tid)) {
                $this->createNewCategoryTerm($tid, $name);
            }
        }

        // Second loop iterates over the events and creates/updates the node(s)
        foreach ($events as $event) {
            $nids = $this->queryEventNode($event->id);
            $this->createEventNode($event);
        }
    }

    /**
     * @param $event_id
     * @return mixed
     */
    public function queryEventNode($event_id)
    {
        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'event');
        $query->condition('field_libcal_id', $event_id);
        return $query->execute();
    }

    public function queryEventCategoryExists($tid) {
        $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($tid);
        if (isset($term)) {
            return true;
        } else {
            return false;
        }
    }

    public function createNewCategoryTerm($tid, $name) {
        $term_create = Term::create(array('name' => $name, 'tid' => $tid, 'vid' => 'event_categories' ))->save();
    }

    public function updatePastFieldEventNode($config)
    {
        $now = new DrupalDateTime();
        $now->setTimezone(new \DateTimeZone('UTC'));

        // Begin constructing the query
        // All event types with a start date in the past
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'event');
        $query->condition('field_start_date', $now, '<');

        // If user has requested that nodes be deleted, do so.
        // Otherwise, set a flag indicating this is a past event.
        if ($config->get('remove-past-events')) {
            $nids = $query->execute();
            $c = count($nids);

            foreach (array_values($nids) as $nid) {
                // Delete the node
                $node = Node::load($nid);
                $startdate = $node->field_start_date->value;
                $libcalid = $node->field_libcal_id->value;
                $node->delete();
            }
        } else {
            // Add condition to query to only get events that haven't already been flagged as "past"
            $query->condition('field_past_event', false);
            $nids = $query->execute();

            $c = count($nids);

            foreach (array_values($nids) as $nid) {
                $node = Node::load($nid);
                $startdate = $node->field_start_date->value;
                $libcalid = $node->field_libcal_id->value;
                $node->set('field_past_event', true);
                $node->save();
            }
        }
    }

    /**
     * Deletes event nodes that have been deleted from libcal
     */
    public function deleteEventNodes($events) {
        $now = new DrupalDateTime();
        $now->setTimezone(new \DateTimeZone('UTC'));

        // Get all future events
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'event');
        $query->condition('field_start_date', $now, '>=');
        $nids = $query->execute();

        // Get only the libcal IDs from the events array
        $libcalIDs = array_column($events, 'id');

        foreach ($nids as $nid) {
            $node = Node::load($nid);
            $id = $node->field_libcal_id->value;

            // Check that this ID is present in the events array, delete it if it isn't
            // in_array() is agnostic to strings/ints
            if (!in_array($id, $libcalIDs)) {
                $node->delete();
            }
        }
    }

    /**
     * @param $event
     */
    public function createEventNode($event)
    {
        $startdate_obj = new DrupalDateTime($event->start);
        $startdate_obj->setTimezone(new \DateTimeZone('UTC'));
        $startdate = $startdate_obj->format('Y-m-d\TH:i:s');

        $enddate_obj = new DrupalDateTime($event->end);
        $enddate_obj->setTimezone(new \DateTimeZone('UTC'));
        $enddate = $enddate_obj->format('Y-m-d\TH:i:s');

        $category_tid_list = array();
        foreach ($event->category as $category) {
            array_push($category_tid_list, array('target_id' => $category->id));
        }

        $nodeParams = [
            // The node entity bundle.
            'type' => 'event',
            'langcode' => 'en',
            // The user ID.
            'uid' => 1,
            'moderation_state' => 'published'
        ];

        $libcalParams = [
            'title' => $event->title,
            'body' => [
                'summary' => substr(strip_tags($event->description), 0, 100),
                'value' => str_replace("<p>&nbsp;</p>", "", $event->description),
                'format' => 'full_html'
            ],
            'field_start_date' => $startdate,
            'field_end_date' => $enddate,
            'field_libcal_id' => $event->id, // need to make sure it's unique
            'field_libcal_featured_image' => $event->featured_image,
            'field_libcal_url' => $event->url->public,
            'field_all_day' => $event->allday,
            'field_calendar_id' => $event->calendar->id,
            'field_calendar_name' => $event->calendar->name,
            'field_campus' => (isset($event->campus) && is_object($event->campus) && isset($event->campus->name)) ? $event->campus->name : "",
            'field_libcal_categories' => $category_tid_list,
            'field_libcal_color' => $event->color,
            'field_location' => $event->location->name,
            'field_presenter' => $event->presenter,
            'field_registration' => $event->registration,
            'field_seats' => $event->seats,
            'field_seats_taken' => !empty($event->seats_taken)? $event->seats_taken: 0,
            'field_wait_list' => !empty($event->wait_list) ? $event->wait_list: 0,
            'field_past_event' => 0,
            'field_geolocation_latitude' => (isset($event->geolocation->latitude)) ? $event->geolocation->latitude : null,
            'field_geolocation_longitude' => (isset($event->geolocation->longitude)) ? $event->geolocation->longitude : null,
            'field_geolocation_place_id' => (isset($event->geolocation->{'place-id'})) ? $event->geolocation->{'place-id'} : null
        ];

        $newNode = Node::create(array_merge($nodeParams, $libcalParams));

        // Determine if we should create a new node, or update an existing one
        $nids = $this->queryEventNode($event->id);

        if (count($nids) <= 0) {
            // New node, so just save it and we're done.
            $newNode->set('created', time());
            $newNode->set('changed', time());
            $newNode->save();
        } else {
            $currentNode = Node::load(array_values($nids)[0]);
            $updateRequired = false;

            foreach (array_keys($libcalParams) as $libcalField) {
                // Get the currently set and expected values
                $cv = $currentNode->get($libcalField)->value;
                $nv = $newNode->get($libcalField)->value;

                // If they do not match
                if ($cv != $nv) {
                    $updateRequired = true;
                    // body field requires special treatment
                    $b = 'body';
                    if ($libcalField == $b) {
                        $currentNode->set(
                            $b,
                            [
                                'summary' => $newNode->get($b)->summary,
                                'value' => $newNode->get($b)->value,
                                'format' => 'full_html'
                            ]
                        );
                    } else {
                        $currentNode->set($libcalField, $nv);
                    }
                }
            }

            // Only update the node if there were changes
            if ($updateRequired) {
                $currentNode->set('changed', time());
                $currentNode->save();
            }
        }
    }
}
