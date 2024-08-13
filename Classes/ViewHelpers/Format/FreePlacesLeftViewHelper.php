<?php
namespace Slub\SlubEvents\ViewHelpers\Format;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Bigga <typo3@slub-dresden.de>, SLUB Dresden
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

use \Slub\SlubEvents\Domain\Model\Event;
use \Slub\SlubEvents\Domain\Repository\SubscriberRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Calculate Free Places
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class FreePlacesLeftViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('event', Event::class, 'Events', true);
    }

    /**
     * subscriberRepository
     *
     * @var SubscriberRepository
     */
    protected static $subscriberRepository = null;

    /**
     * Calculate the free places for a given event.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $event = $arguments['event'];
        if ($event != null) {
            $free = $event->getMaxSubscriber() - self::getSubscriberRepository()->countAllByEvent($event);
        } else {
            $free = 0;
        }

        return ($free > 0) ? $free : 0;
    }

    /**
     * Initialize the subscriberRepository
     *
     * return SubscriberRepository
     */
    private static function getSubscriberRepository()
    {
        if (null === static::$subscriberRepository) {
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            static::$subscriberRepository = $objectManager->get(SubscriberRepository::class);
        }

        return static::$subscriberRepository;
    }

}
