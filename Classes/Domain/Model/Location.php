<?php
namespace Slub\SlubEvents\Domain\Model;

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

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @package slub_events
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Location extends AbstractEntity
{

    /**
     * Name of the Location
     *
     * @Extbase\Validate("NotEmpty")
     * @var string
     */
    protected $name;

    /**
     * description of the location
     *
     * @var string
     */
    protected $description;

    /**
     * Link to further information
     *
     * @var string
     */
    protected $link;

    /**
     * parent
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Slub\SlubEvents\Domain\Model\Location>
     */
    protected $parent;

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description
     *
     * @param string $description
     *
     * @return void
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the link
     *
     * @return string $link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Sets the link
     *
     * @param string $link
     *
     * @return void
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Location constructor.
     */
    public function __construct()
    {
        //Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    /**
     * Initializes all \TYPO3\CMS\Extbase\Persistence\ObjectStorage properties.
     *
     * @return void
     */
    protected function initStorageObjects()
    {
        /**
         * Do not modify this method!
         * It will be rewritten on each save in the extension builder
         * You may modify the constructor of this class instead
         */
        $this->parent = new ObjectStorage();
    }

    /**
     * Adds a Location
     *
     * @param \Slub\SlubEvents\Domain\Model\Location $parent
     *
     * @return void
     */
    public function addParent(\Slub\SlubEvents\Domain\Model\Location $parent)
    {
        $this->parent->attach($parent);
    }

    /**
     * Removes a Location
     *
     * @param \Slub\SlubEvents\Domain\Model\Location $parentToRemove The Location to be removed
     *
     * @return void
     */
    public function removeParent(\Slub\SlubEvents\Domain\Model\Location $parentToRemove)
    {
        $this->parent->detach($parentToRemove);
    }

    /**
     * Returns the parent
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Slub\SlubEvents\Domain\Model\Location> $parent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets the parent
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Slub\SlubEvents\Domain\Model\Location> $parent
     *
     * @return void
     */
    public function setParent(ObjectStorage $parent)
    {
        $this->parent = $parent;
    }
}
