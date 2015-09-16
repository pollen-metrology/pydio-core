<?php
/*
 * Copyright 2007-2015 Abstrium <contact (at) pydio.com>
 * This file is part of Pydio.
 *
 * Pydio is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Pydio is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Pydio.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <http://pyd.io/>.
 */

defined('AJXP_EXEC') or die('Access not allowed');


class AJXP_Permission
{
    /**
     * Use an integer number to store permission
     *  |r|w|d|t|....|..
     *  |0|1|0|0|....|..
     */

    const MASK = 15; // 0000..001111 means use 4 first bits for encoding permission. The rest will be cut off.
    const READ = 1; // 0001
    const WRITE = 2; // 0010
    const DENY = 4; // 0100
    const TRAVEL = 8; //1000

    private $value = 0;

    /**
     * @param array|null $value
     */
    function __construct($value = null){
        if($value != null){
            if(is_array($value)) $this->value = $value;
            elseif(is_integer($value)) {
                $this->value = $value & self::MASK;
            }
            elseif(is_string($value)){
                if(strpos($value, "r") !== false) $this->setRead();
                if(strpos($value, "w") !== false) $this->setWrite();
                if(strpos($value, "d") !== false) $this->setDeny();
            }
        }
    }

    function getCopy(){
        return new AJXP_Permission($this->value);
    }

    /**
     * @return bool
     */
    function canRead(){
        return ($this->value & self::READ) === self::READ;
    }

    /**
     * @return bool
     */
    function canWrite(){
        return ($this->value & self::WRITE) === self::WRITE;
    }

    /**
     * @return bool
     */
    function denies(){
        if ($this->value === self::DENY) return true;
        if ($this->value === 0) return true;
        return false;
    }

    function testPermission($numPerm){
        if(is_integer($numPerm) && ($numPerm < self::MASK)){
            $numPerm = $numPerm & self::MASK;
            if (($this->value !== 0) && $numPerm === 0) return false;
            if (($this->value === 0) && $numPerm === self::DENY) return true;
            return (($this->value & $numPerm) === $numPerm);
        }
        else{
            throw new Exception("Unimplemented permission : " . $numPerm);
        }
    }

    function setRead($value = true){
        if($value)
            $this->value = $this->value | self::READ;
        else{
            $this->value = $this->value & (self::READ ^ self::MASK);
        }
    }
    function setWrite($value = true){
        if($value)
            $this->value = $this->value | self::WRITE;
        else{
            $this->value = $this->value & (self::WRITE ^ self::MASK);
        }
    }
    function setDeny($value = true){
        if($value)
            $this->value = $this->value & self::DENY;
        else{
            $this->value = $this->value & (self::DENY ^ self::MASK);
        }
    }

    /**
     * @param AJXP_Permission $perm
     * @return AJXP_Permission
     */
    function override($perm){
        $newPerm = $perm->getCopy();
        if($this->denies()){
            $newPerm->setDeny();
        }else{
            if($this->canRead())
                $newPerm->setRead();
            if($this->canWrite())
                $newPerm->setWrite();
        }
        return $newPerm;
    }

    function __toString(){
        if($this->denies()) {
            return "DENY";
        }else if($this->canRead() && !$this->canWrite()) {
            return "READONLY";
        }else if(!$this->canRead() && $this->canWrite()) {
            return "WRITEONLY";
        }else{
            return "READ WRITE";
        }
    }
}