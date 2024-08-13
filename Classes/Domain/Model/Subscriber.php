<?php
namespace Slub\SlubEvents\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Alexander Bigga <typo3@slub-dresden.de>, SLUB Dresden
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
use Slub\SlubEvents\Domain\Model\Event;

/**
 * @package slub_events
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class Subscriber extends AbstractEntity
{
    /**
     * name
     *
     * @Extbase\Validate("NotEmpty")
     * @var string
     */
    protected $name;

    /**
     * email
     *
     * @Extbase\Validate("NotEmpty")
     * @var string
     */
    protected $email;

    /**
     * telephone
     *
     * @var string
     */
    protected $telephone;

    /**
     * institution
     *
     * @var string
     */
    protected $institution;

    /**
     * customerid
     *
     * @var string
     */
    protected $customerid;

    /**
     * Number of Subscribers
     *
     * @var integer
     */
    protected $number = 1;

    /**
     * Message by the Customer
     *
     * @var string
     */
    protected $message;

    /**
     * Edit Code
     *
     * @Extbase\Validate("NotEmpty")
     * @var string
     */
    protected $editcode;

    /**
     * Accept privacy policy
     *
     * @var bool
     */
    protected $acceptpp;

    /**
     * Creation Date
     *
     * @var \DateTime
     */
    protected $crdate;

    /**
     * event
     *
     * @var \Slub\SlubEvents\Domain\Model\Event
     */
    protected $event;

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
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the email
     *
     * @return string $email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Returns the event
     *
     * @return \Slub\SlubEvents\Domain\Model\Event $event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Sets the email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Returns the telephone
     *
     * @return string $telephone
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Sets the telephone
     *
     * @param string $telephone
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }

    /**
     * Returns the institution
     *
     * @return string $institution
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * Sets the institution
     *
     * @param string $institution
     */
    public function setInstitution($institution)
    {
        $this->institution = $institution;
    }

    /**
     * Returns the customerid
     *
     * @return string $customerid
     */
    public function getCustomerid()
    {
        return $this->customerid;
    }

    /**
     * Sets the customerid
     *
     * @param string $customerid
     */
    public function setCustomerid($customerid)
    {
        $this->customerid = $customerid;
    }

    /**
     * Returns the number
     *
     * @return integer number
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Sets the number
     *
     * @param integer $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Returns the editcode
     *
     * @return string $editcode
     */
    public function getEditcode()
    {
        return $this->editcode;
    }

    /**
     * Sets the editcode
     *
     * @param string $editcode
     */
    public function setEditcode($editcode)
    {
        $this->editcode = $editcode;
    }

    /**
     * Returns the message
     *
     * @return string $message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets the message
     *
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Returns true or false for accepted privacy policy
     *
     * @return bool
     */
    public function getAcceptpp()
    {
        return $this->acceptpp;
    }

    /**
     * Sets acceptpp
     *
     * @param bool $acceptpp
     */
    public function setAcceptpp($acceptpp)
    {
        $this->acceptpp = $acceptpp;
    }

    /**
     * Returns the crdate
     *
     * @return \DateTime $crdate
     */
    public function getCrdate()
    {
        return $this->crdate;
    }

    /**
     * Sets the crdate
     *
     * @param \DateTime $crdate
     */
    public function setCrdate($crdate)
    {
        $this->crdate = $crdate;
    }

    /**
     * Sets the event
     *
     * @param \Slub\SlubEvents\Domain\Model\Event $event
     *
     * @return void
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;
    }
}
