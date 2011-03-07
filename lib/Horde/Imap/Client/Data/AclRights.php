<?php
/**
 * Available ACL rights for a mailbox/identifier (see RFC 2086/4314).
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 */
class Horde_Imap_Client_Data_AclRights implements ArrayAccess, Iterator, Serializable
{
    /**
     * ACL optional rights.
     *
     * @var array
     */
    protected $_optional = array();

    /**
     * ACL required rights.
     *
     * @var array
     */
    protected $_required = array();

    /**
     * Constructor.
     *
     * @var array $required  The required rights (see RFC 4314 [2.1]).
     * @var array $optional  The optional rights (see RFC 4314 [2.1]).
     */
    public function __construct(array $required = array(),
                                array $optional = array())
    {
        $this->_required = $required;

        foreach ($optional as $val) {
            foreach (str_split($val) as $right) {
                $this->_optional[$right] = $val;
            }
        }

        // Clients conforming to RFC 4314 MUST ignore the virtual ACL_CREATE
        // and ACL_DELETE rights. See RFC 4314 [2.1].
        if ($this[Horde_Imap_Client::ACL_CREATE] &&
            $this[Horde_Imap_Client::ACL_CREATEMBOX]) {
            unset($this[Horde_Imap_Client::ACL_CREATE]);
        }
        if ($this[Horde_Imap_Client::ACL_DELETE] &&
            $this[Horde_Imap_Client::ACL_DELETEMSGS]) {
            unset($this[Horde_Imap_Client::ACL_DELETE]);
        }
    }

    /**
     * String representation of the ACL.
     *
     * @return string  String representation.
     */
    public function __toString()
    {
        return array_keys(array_flip(array_merge(array_values($this->_required), array_keys($this->_optional))));
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        return (bool)$this[$offset];
    }

    /**
     */
    public function offsetGet($offset)
    {
        if (isset($this->_optional[$offset])) {
            return $this->_optional[$offset];
        }

        $pos = array_search($offset, $this->_required);

        return ($pos === false)
            ? null
            : $this->_required[$pos];
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        $this->_optional[$offset] = $value;
    }

    /**
     */
    public function offsetUnset($offset)
    {
        unset($this->_optional[$offset]);
        $this->_required = array_values(array_diff($this->_required, array($offset)));
    }

    /* Iterator methods. */

    /**
     */
    public function current()
    {
        $val = current($this->_required);
        return is_null($val)
            ? current($this->_optional)
            : $val;
    }

    /**
     */
    public function key()
    {
        $key = key($this->_required);
        return is_null($key)
            ? key($this->_optional)
            : $key;
    }

    /**
     */
    public function next()
    {
        if (key($this->_required) === null) {
            next($this->_optional);
        } else {
            next($this->_required);
        }
    }

    /**
     */
    public function rewind()
    {
        reset($this->_required);
        reset($this->_optional);
    }

    /**
     */
    public function valid()
    {
        return ((key($this->_required) !== null) ||
                (key($this->_optional) !== null));

    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode(array(
            $this->_required,
            $this->_optional
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        list($this->_required, $this->_optional) = json_decode($data);
    }

}