<?php
namespace Slub\SlubEvents\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2014 Alexander Bigga <typo3@slub-dresden.de>, SLUB Dresden
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use Slub\SlubEvents\Domain\Model\Event;
use Slub\SlubEvents\Helper\EmailHelper;
use Slub\SlubEvents\Helper\EventHelper;
use Slub\SlubEvents\Utility\TextUtility;

/**
 * @package slub_events
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class EventController extends AbstractController
{

    /**
     * Initializes the current action
     *
     * idea from tx_news extension
     *
     * @return void
     */
    public function initializeAction(): void
    {

        // Only do this in Frontend Context
        if (!empty($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {
            // We only want to set the tag once in one request, so we have to cache that statically if it has been done
            static $cacheTagsSet = false;

            /** @var $typoScriptFrontendController \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController */
            $typoScriptFrontendController = $GLOBALS['TSFE'];
            if (!$cacheTagsSet) {
                $typoScriptFrontendController->addCacheTags(
                    [1 => 'tx_slubevents_' . $this->settings['storagePid']]
                );
                $cacheTagsSet = true;
            }
        }
    }

    /**
     * action list
     *
     * @return void
     */
    public function listAction(): void
    {
        if (!empty($this->settings['categorySelection'])) {
            $this->settings['categoryList'] = $this->getCategoryIdsFromSettings();
        }

        if (!empty($this->settings['disciplineSelection'])) {
            $this->settings['disciplineList'] = $this->getDisciplineIdsFromSettings();
        }

        $events = $this->eventRepository->findAllBySettings($this->settings);

        $this->view->assign('events', $events);
    }

    /**
     * action listUpcomming
     *
     * @return void
     */
    public function listUpcomingAction(): void
    {
        if (!empty($this->settings['categorySelection'])) {
            $this->settings['categoryList'] = $this->getCategoryIdsFromSettings();
        }

        if (!empty($this->settings['disciplineSelection'])) {
            $this->settings['disciplineList'] = $this->getDisciplineIdsFromSettings();
        }

        $events = $this->eventRepository->findAllBySettings($this->settings);

        $this->view->assign('events', $events);
    }

    /**
     * action show
     *
     * @param Event $event
     * @Extbase\IgnoreValidation("event")
     *
     * @return void
     */
    public function showAction(Event $event = null): void
    {
        if ($event !== null) {
            $shortDescription = $event->getTeaser() ?: $event->getDescription();
            // get description and cut to 200 chars, strip tags its an rte field
            $shortDescription = substr( strip_tags( $shortDescription ) , 0, 200);
            // cut it at last space
            $shortDescription = trim(substr($shortDescription , 0, strrpos($shortDescription, ' ')));

            // fill registers to be used in ts
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $cObj->cObjGetSingle('LOAD_REGISTER',
                [
                    'eventPageTitle' =>
                        LocalizationUtility::translate(
                            'tx_slubevents_domain_model_event',
                            'slub_events'
                        )
                        . ': "' . $event->getTitle() . '" - ' . strftime(
                            '%a, %x %H:%M',
                            $event->getStartDateTime()->getTimeStamp()
                        ),
                    'eventPageDescription' => $shortDescription
                ]
            );
        }

        $this->view->assign('event', $event);
    }

    /**
     * action showNotfound
     *
     * @return void
     */
    public function showNotFoundAction(): void
    {
    }

    /**
     * action new
     *
     * @param Event $newEvent
     * @Extbase\IgnoreValidation("newEvent")
     *
     * @return void
     */
    public function newAction(Event $newEvent = null): void
    {
        $this->view->assign('newEvent', $newEvent);
    }

    /**
     * action create
     *
     * @param Event $newEvent
     *
     * @return void
     */
    public function createAction(Event $newEvent): void
    {
        $this->eventRepository->add($newEvent);
        $this->addFlashMessage('Your new Event was created.');
        $this->redirect('list');
    }

    /**
     * action edit
     *
     * @param Event $event
     * @Extbase\IgnoreValidation("event")
     *
     * @return void
     */
    public function editAction(Event $event): void
    {
        $this->view->assign('event', $event);
    }

    /**
     * action update
     *
     * @param Event $event
     *
     * @return void
     */
    public function updateAction(Event $event): void
    {
        $this->eventRepository->update($event);
        $this->addFlashMessage('Your Event was updated.');
        $this->redirect('list');
    }

    /**
     * action delete
     *
     * @param Event $event
     *
     * @return void
     */
    public function deleteAction(Event $event): void
    {
        $this->eventRepository->remove($event);
        $this->addFlashMessage('Your Event was removed.');
        $this->redirect('list');
    }

    /**
     * action listOwn
     *
     * @return void
     */
    public function listOwnAction(): void
    {

        // + the user is logged in
        // + the username == customerid
        $subscribers = $this->subscriberRepository->findAllByFeuser();

        $events = $this->eventRepository->findAllBySubscriber($subscribers);

        $this->view->assign('subscribers', $subscribers);
        $this->view->assign('events', $events);
    }

    /**
     * action listMonth
     *
     * @return void
     */
    public function listMonthAction(): void
    {
        if (!empty($this->settings['categorySelection'])) {
            $categoriesIds = $this->getCategoryIdsFromSettings();
            $this->settings['categoryList'] = $categoriesIds;
            $categories = $this->categoryRepository->findAllByUidsTree($this->settings['categoryList']);
        }

        if (!empty($this->settings['disciplineSelection'])) {
            $disciplineIds = $this->getDisciplineIdsFromSettings();
            $this->settings['disciplineList'] = $disciplineIds;
            $disciplines = $this->disciplineRepository->findAllByUidsTree($this->settings['disciplineList']);
        }

        $this->view->assign('categories', $categories);
        $this->view->assign('disciplines', $disciplines);
        $this->view->assign('categoriesIds', explode(',', $this->settings['categorySelection']));
        $this->view->assign('disciplinesIds', explode(',', $this->settings['disciplineSelection']));
    }

    /**
     * Initializes the create childs action
     * @param integer $id
     *
     * @return void
     */
    public function initializeCreateChildsAction($id): void
    {
        // this does not work reliable in this context (--> has to be verified again!)
        // as the childs must be on the same storage pid as the parent, we take
        // the pid and set is as storagePid
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
        ->getQueryBuilderForTable('tx_slubevents_domain_model_event');

        $result = $queryBuilder
            ->select('uid', 'pid')
            ->from('tx_slubevents_domain_model_event')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter((int) $id, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute();

        if ($resArray = $result->fetch()) {
          $parentEventRow = $resArray;
        }

        $this->settings['storagePid'] = $parentEventRow['pid'];
        // set storagePid to point extbase to the right repositories
        $configurationArray = [
            'persistence' => [
                'storagePid' => $parentEventRow['pid'],
            ],
        ];
        $this->configurationManager->setConfiguration($configurationArray);
    }

    /**
     * create or update child events for given event id
     *
     * @param integer $id
     *
     * @return void
     */
    public function createChildsAction($id): void
    {
        $this->initializeCreateChildsAction($id);

        $parentEvent = $this->eventRepository->findOneByUidIncludeHidden($id);

        if ($parentEvent) {

            $childDateTimes = $this->getChildDateTimes($parentEvent);

            $availableProperties = ObjectAccess::getGettablePropertyNames($parentEvent);

            // delete all present child events which are not requested (e.g. from former settings)
            $this->eventRepository->deleteAllNotAllowedChildren($childDateTimes, $parentEvent);

            foreach ($childDateTimes as $childDateTime) {

                $isUpdate = FALSE;

                $childEvent = $this->eventRepository->findOneByStartDateTimeAndParent($childDateTime['startDateTime'], $parentEvent);

                // a childevent for the given startDateTime already exists
                if ($childEvent) {
                    $isUpdate = TRUE;
                } else {
                    // no child event found - create a new one
                    /** @var Event $childEvent */
                    $childEvent = $this->objectManager->get(Event::class);
                }

                foreach ($availableProperties as $propertyName) {
                    if (ObjectAccess::isPropertySettable($childEvent, $propertyName)
                        && !in_array($propertyName, [
                            'uid',
                            'pid',
                            'hidden',
                            'parent',
                            'recurring',
                            'recurring_options',
                            'recurring_end_date_time',
                            'startDateTime',
                            'endDateTime',
                            'subscribers',
                            'cancelled',
                            'subEndDateTime',
                            'subEndDateInfoSent',
                            'categories',
                            'discipline',
                        ])
                    ) {
                        $propertyValue = ObjectAccess::getProperty($parentEvent, $propertyName);
                        // special handling for onlinesurvey field to remove trailing timestamp with sent date
                        if ($propertyName == 'onlinesurvey' && (strpos($propertyValue, '|') > 0)) {
                            $propertyValue = substr($propertyValue, 0, strpos($propertyValue, '|'));
                        }
                        ObjectAccess::setProperty($childEvent, $propertyName, $propertyValue);
                    }
                }

                $childEvent->setParent($parentEvent);

                $childEvent->setStartDateTime($childDateTime['startDateTime']);

                $childEvent->setEndDateTime($childDateTime['endDateTime']);

                if ($childDateTime['subEndDateTime']) {
                    $childEvent->setSubEndDateTime($childDateTime['subEndDateTime']);
                }

                foreach ($parentEvent->getCategories() as $cat) {
                    $childEvent->addCategory($cat);
                }

                foreach ($parentEvent->getDiscipline() as $discipline) {
                    $childEvent->addDiscipline($discipline);
                }

                if ($parentEvent->getGeniusBar()) {
                    $childEvent->setTitle('Wissensbar ' . $childEvent->getContact()->getName());
                } else {
                    $childEvent->setTitle($childEvent->getTitle());
                }

                if ($isUpdate === TRUE) {
                    $this->eventRepository->update($childEvent);
                } else {
                    $this->eventRepository->add($childEvent);
                }

            }

            $persistenceManager = $this->objectManager->get(PersistenceManager::class);
            $persistenceManager->persistAll();
        }

    }

    /**
     * delete child events for given event id
     *
     * @param integer $id
     *
     * @return void
     */
    public function deleteChildsAction($id): void
    {
        $this->initializeCreateChildsAction($id);

        $parentEvent = $this->eventRepository->findOneByUid($id);

        if ($parentEvent) {

            // delete all present child events
            $this->eventRepository->deleteAllNotAllowedChildren(array(), $parentEvent);

            $persistenceManager = $this->objectManager->get(PersistenceManager::class);
            $persistenceManager->persistAll();

        }

    }

    /**
     * action errorAction
     *
     * @return void
     */
    public function errorAction(): void
    {
    }


    /**
     * action ajax
     *
     * EXPERIMENTAL!!
     *
     * @return string
     */
    public function ajaxAction(): string
    {
        $jsonevent = [];

        // we do a simple file caching for performance reasons
        // compose the filename
        $calfile = Environment::getPublicPath() . '/typo3temp/tx_slubevents/calfile_' . md5(GeneralUtility::_GET('disciplines') . GeneralUtility::_GET('categories')) . '_' . strtotime(GeneralUtility::_GET('start')) . '_' . strtotime(GeneralUtility::_GET('end')) . '.json';
        // if file exists and is not too old - take it
        if (file_exists($calfile)) {
            // if not older than one day:
            if ((time() - filemtime($calfile) < 86400)) {
                $fp = fopen($calfile, 'r');
                fpassthru($fp);
                exit;
            }
        }

        // no valid caching file --> we do a new query and save the result
        $events = $this->eventRepository->findAllBySettings([
            'categoryList'   => GeneralUtility::intExplode(',', GeneralUtility::_GET('categories'), true),
            'disciplineList' => GeneralUtility::intExplode(',', GeneralUtility::_GET('disciplines'), true),
            'startTimestamp' => strtotime(GeneralUtility::_GET('start')),
            'stopTimestamp'  => strtotime(GeneralUtility::_GET('end')),
            'showPastEvents' => true,
        ]);

        $cObj = $this->configurationManager->getContentObject();
        /** @var Event $event */
        foreach ($events as $event) {
            $foundevent = [];

            $foundevent['id'] = $event->getUid();
            $foundevent['title'] = $event->getTitle();
            $foundevent['teaser'] = $event->getTeaser();
            $foundevent['start'] = $event->getStartDateTime()->format('Y-m-d H:i:s');
            foreach ($event->getCategories() as $cat) {
                $foundevent['className'] .= ' slubevents-category-' . $cat->getUid();
            }

            if ($event->getEndDateTime() instanceof \DateTime) {
                $foundevent['end'] = $event->getEndDateTime()->format('Y-m-d H:i:s');
            }

            $conf = [
                // Link to current page
                'parameter'        => GeneralUtility::_GET('detailPid'),
                // Set additional parameters
                'additionalParams' => '&type=0&tx_slubevents_eventlist%5Bevent%5D=' . $event->getUid() . '&tx_slubevents_eventlist%5Baction%5D=show',
                // We must add cHash because we use parameters
                'useCacheHash'     => 1,
                // We want link only
                'returnLast'       => 'url',
            ];
            $url = $cObj->typoLink('', $conf);
            $foundevent['url'] = $url;

            if ($event->getAllDay()) {
                $foundevent['allDay'] = true;
            } else {
                $foundevent['allDay'] = false;
            }

            // how many free places are available?
            $freePlaces = ($event->getMaxSubscriber() - $this->subscriberRepository->countAllByEvent($event));
            if ($freePlaces <= 0) {
                $foundevent['freePlaces'] = 0;
            } else {
                if ($freePlaces == 1) {
                    $foundevent['freePlaces'] = LocalizationUtility::translate(
                        'tx_slubevents_domain_model_event.oneFreePlace',
                        'slub_events'
                    );
                } else {
                    $foundevent['freePlaces'] =
                        ($event->getMaxSubscriber() - $this->subscriberRepository->countAllByEvent($event));

                    $foundevent['freePlaces'] .= ' ' .
                        LocalizationUtility::translate(
                            'tx_slubevents_domain_model_event.freeplaces',
                            'slub_events'
                        );
                }
            }

            // set special css class if subscription is NOT possible
            $noSubscription = false;
            // limit reached already --> overbooked
            if ($this->subscriberRepository->countAllByEvent($event) >= $event->getMaxSubscriber()) {
                $noSubscription = true;
            }
            // event is cancelled
            if ($event->getCancelled()) {
                $noSubscription = true;
            }
            // deadline reached....
            if (is_object($event->getSubEndDateTime())) {
                if ($event->getSubEndDateTime()->getTimestamp() < time()) {
                    $noSubscription = true;
                }
            }
            if ($noSubscription) {
                $foundevent['className'] .= ' no_subscription';
            }
            $jsonevent[] = $foundevent;
        }

        $outputJson = json_encode($jsonevent);

        // cache the output for further requests
        $fp = fopen($calfile, 'w');
        if ($fp) {
            fwrite($fp, $outputJson);
            fclose($fp);
        }

        return $outputJson;
    }

    /**
     * action printCal
     *
     * @param Event $event
     * @Extbase\IgnoreValidation("event")
     *
     * @return void
     */
    public function printCalAction(Event $event = null): void
    {
		if ($event === null) {
            $this->redirect('showNotFound');
        } else {
            $helper['now'] = time();
            $helper['start'] = $event->getStartDateTime()->getTimestamp();
            // endDate may be empty
            if ($event->getEndDateTime() instanceof \DateTime && $event->getStartDateTime() != $event->getEndDateTime()) {
                $helper['end'] = $event->getEndDateTime()->getTimestamp();
            } else {
                $helper['allDay'] = 1;
                $helper['end'] = $helper['start'];
            }
            $helper['description'] = TextUtility::foldline(EmailHelper::html2rest($event->getDescription()));
            $helper['location'] = EventHelper::getLocationNameWithParent($event);
            $helper['locationics'] = TextUtility::foldline($helper['location']);
            $this->view->assign('helper', $helper);
            $this->view->assign('event', $event);
        }
    }

    /**
     * calculate all child dateTime fields (start_date_time, end_date_time, sub_end_date_time ...)
     *
     * @param Event $parentEvent
     *
     * @return array
     */
    public function getChildDateTimes($parentEvent): array
    {
        $recurring_options = $parentEvent->getRecurringOptions();
        $recurringEndDateTime = $parentEvent->getRecurringEndDateTime();

        $parentStartDateTime = $parentEvent->getStartDateTime();
        $parentEndDateTime = $parentEvent->getEndDateTime();
        $parentSubEndDateTime = $parentEvent->getSubEndDateTime();

        if (!$recurringEndDateTime) {
          // if no recurringEndDateTime is given, set it to ... 3 months for now
          $recurringEndDateTime = clone $parentStartDateTime;
          $recurringEndDateTime->add(new \DateInterval("P3M"));
        }

        $sumDiffDays = 0;
        // interval to parent or previous event date (used for recurring on multiple days per week)
        $diffDays = [];
        // helper array to calculate the day intervall from parent event
        $weekdaysAfterParent = [];

        foreach($recurring_options['weekday'] as $id => $weekday) {
            if ($weekday < $parentStartDateTime->format('N')) {
                $weekdaysAfterParent[] = $weekday + 7;
            } else if ($weekday > $parentStartDateTime->format('N')){
                $weekdaysAfterParent[] = $weekday;
            }
        }

        sort($weekdaysAfterParent);

        foreach($weekdaysAfterParent as $id => $item) {
            $nextEventWeekday = $item - $parentStartDateTime->format('N') - $sumDiffDays;
            $sumDiffDays += $nextEventWeekday;
            $diffDays[] = new \DateInterval("P" . $nextEventWeekday . "D");
        }

        $childDateTimes = []; // will hold all child events

        // start cloning
        $eventStartDateTime = clone $parentStartDateTime;
        $eventEndDateTime = clone $parentEndDateTime;
        if ($parentSubEndDateTime) {
            $eventSubEndDateTime = clone $parentSubEndDateTime;
        }
        switch ($recurring_options['interval']) {
            case 'weekly':
                  $dateTimeInterval = new \DateInterval("P1W");
                  break;
            case '2weekly':
                  $dateTimeInterval = new \DateInterval("P2W");
                  break;
            case '4weekly':
                  $dateTimeInterval = new \DateInterval("P4W");
                  break;
            case 'monthly':
                  $dateTimeInterval = new \DateInterval("P1M");
                  break;
            case 'yearly':
                  $dateTimeInterval = new \DateInterval("P1Y");
                  break;
        }
        $adjustDlstRun = 1;

        //  we need to calculate the transitions out of a new DateTimeZone object
        $timeZone = new \DateTimeZone(date_default_timezone_get());

        // first make events within the first week
        // create the child days within a week
        if (!empty($diffDays)) {
            $diffDayEventStartDateTime = clone $eventStartDateTime;
            $diffDayEventEndDateTime = clone $eventEndDateTime;
            if ($eventSubEndDateTime) {
                $diffDayEventSubEndDateTime = clone $eventSubEndDateTime;
            }
            $adjustDlstDone = FALSE;
            foreach ($diffDays as $weekDayInterval) {
                $diffDayEventStartDateTime->add($weekDayInterval);
                $transitions = $timeZone->getTransitions($eventStartDateTime->getTimestamp(), $diffDayEventStartDateTime->getTimestamp());
                // if there is a transition between startDateStamp and
                // following weekday in series adjust only once the offset.
                if ($transitions && count($transitions) > 1 && $adjustDlstDone === FALSE) {
                    $adjustDlstDone = TRUE;
                    // there seems to be a dailight saving switch
                    $last_transition = array_pop($transitions);
                    $previous_transition = array_pop($transitions);
                    $daylightOffset = $previous_transition['offset'] - $last_transition['offset'];
                } else {
                    $daylightOffset = 0;
                }

                $this->daylightOffset($diffDayEventStartDateTime, $daylightOffset);
                $diffDayEventEndDateTime->add($weekDayInterval);
                $this->daylightOffset($diffDayEventEndDateTime, $daylightOffset);
                if ($diffDayEventSubEndDateTime) {
                    $diffDayEventSubEndDateTime->add($weekDayInterval);
                    $this->daylightOffset($diffDayEventSubEndDateTime, $daylightOffset);
                }

                // single child event --> initialize new array
                $childDateTime = [];
                $childDateTime['endDateTime'] = clone $diffDayEventEndDateTime;
                $childDateTime['startDateTime'] = clone $diffDayEventStartDateTime;
                if ($eventSubEndDateTime) {
                    $childDateTime['subEndDateTime'] = clone $diffDayEventSubEndDateTime;
                }
                if ($childDateTime['startDateTime'] < $recurringEndDateTime){
                    $childDateTimes[] = $childDateTime;
                }
            }
        }
        // second make events in selected intervals
        do {
            $eventStartDateTime->add($dateTimeInterval);
            $daylightOffset = 0;

            $transitions = $timeZone->getTransitions($parentStartDateTime->getTimestamp(), $eventStartDateTime->getTimestamp());

            if ($transitions && count($transitions) > 1 && $adjustDlstRun < count($transitions)) {
                // only adjust offset once
                $adjustDlstRun++;
                // there seems to be a dailight saving switch
                $last_transition = array_pop($transitions);
                $previous_transition = array_pop($transitions);
                $daylightOffset = $previous_transition['offset'] - $last_transition['offset'];
            }
            $this->daylightOffset($eventStartDateTime, $daylightOffset);

            $eventEndDateTime->add($dateTimeInterval);
            $this->daylightOffset($eventEndDateTime, $daylightOffset);
            if ($parentSubEndDateTime) {
                $eventSubEndDateTime->add($dateTimeInterval);
                $this->daylightOffset($eventSubEndDateTime, $daylightOffset);
            }
            $childDateTime = [];
            $childDateTime['startDateTime'] = clone $eventStartDateTime;
            $childDateTime['endDateTime'] = clone $eventEndDateTime;
            if ($eventSubEndDateTime) {
                $childDateTime['subEndDateTime'] = clone $eventSubEndDateTime;
            }
            if ($childDateTime['startDateTime'] < $recurringEndDateTime){
                $childDateTimes[] = $childDateTime;
            }

            // create the child days within a week
            if (!empty($diffDays)) {
                $diffDayEventStartDateTime = clone $eventStartDateTime;
                $diffDayEventEndDateTime = clone $eventEndDateTime;
                if ($eventSubEndDateTime) {
                    $diffDayEventSubEndDateTime = clone $eventSubEndDateTime;
                }
                $adjustDlstDone = FALSE;
                foreach ($diffDays as $weekDayInterval) {
                    $diffDayEventStartDateTime->add($weekDayInterval);
                    $transitions = $timeZone->getTransitions($eventStartDateTime->getTimestamp(), $diffDayEventStartDateTime->getTimestamp());
                    // if there is a transition between startDateStamp and
                    // following weekday in series adjust only once the offset.
                    if ($transitions && count($transitions) > 1 && $adjustDlstDone === FALSE) {
                        $adjustDlstDone = TRUE;
                        // there seems to be a dailight saving switch
                        $last_transition = array_pop($transitions);
                        $previous_transition = array_pop($transitions);
                        $daylightOffset = $previous_transition['offset'] - $last_transition['offset'];
                    } else {
                        $daylightOffset = 0;
                    }

                    $this->daylightOffset($diffDayEventStartDateTime, $daylightOffset);
                    $diffDayEventEndDateTime->add($weekDayInterval);
                    $this->daylightOffset($diffDayEventEndDateTime, $daylightOffset);
                    if ($diffDayEventSubEndDateTime) {
                        $diffDayEventSubEndDateTime->add($weekDayInterval);
                        $this->daylightOffset($diffDayEventSubEndDateTime, $daylightOffset);
                    }

                // single child event --> initialize new array
                    $childDateTime = [];
                    $childDateTime['endDateTime'] = clone $diffDayEventEndDateTime;
                    $childDateTime['startDateTime'] = clone $diffDayEventStartDateTime;
                    if ($eventSubEndDateTime) {
                        $childDateTime['subEndDateTime'] = clone $diffDayEventSubEndDateTime;
                    }
                    if ($childDateTime['startDateTime'] < $recurringEndDateTime){
                      $childDateTimes[] = $childDateTime;
                    }
                }
            }
        } while ($eventStartDateTime < $recurringEndDateTime);

        return $childDateTimes;
    }

    /**
     * add offset to given DateTime
     *
     * @param \DateTime $dateTimeValue
     * @param integer $offset in seconds
     *
     * @return void
     */
    private function daylightOffset($dateTimeValue, $offset): void
    {
      if ($offset > 0) {
          $dateTimeValue->add(new \DateInterval('PT'.$offset.'S'));
      } else if ($offset < 0) {
          $dateTimeValue->sub(new \DateInterval('PT'.(-1) * $offset.'S'));
      }
    }

    /**
     * @return array|int[]
     */
    protected function getCategoryIdsFromSettings(): array
    {
        $categoriesIds = GeneralUtility::intExplode(',', $this->settings['categorySelection'], true);

        if ($this->settings['categorySelectionRecursive']) {
            // add somehow the other categories...
            foreach ($categoriesIds as $category) {
                $foundRecusiveCategories = $this->categoryRepository->findAllChildCategories($category);
                if (count($foundRecusiveCategories) > 0) {
                    $categoriesIds = array_merge($foundRecusiveCategories, $categoriesIds);
                }
            }
        }
        return $categoriesIds;
    }

    /**
     * @return array|int[]
     */
    protected function getDisciplineIdsFromSettings(): array
    {
        $disciplineIds = GeneralUtility::intExplode(',', $this->settings['disciplineSelection'], true);

        if ($this->settings['disciplineSelectionRecursive']) {
            // add somehow the other categories...
            foreach ($disciplineIds as $discipline) {
                $foundRecusiveDisciplines = $this->disciplineRepository->findAllChildDisciplines($discipline);
                if (count($foundRecusiveDisciplines) > 0) {
                    $disciplineIds = array_merge($foundRecusiveDisciplines, $disciplineIds);
                }
            }
        }
        return $disciplineIds;
    }
}
