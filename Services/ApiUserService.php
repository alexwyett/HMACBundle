<?php

namespace HMACBundle\Services;

use HMACBundle\Exceptions\APIException;
use HMACBundle\Entity\ApiUser;

/**
 * Handles ApiUser crud
 *
 * @category  Services
 * @package   TOCC
 * @author    Alex Wyett <alex@wyett.co.uk>
 * @copyright 2014 Alex Wyett
 * @license   All rights reserved
 * @link      http://www.wyett.co.uk
 */
class ApiUserService
{
    /**
     * Entity Manager
     * 
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
    
    /**
     * Defined User Roles
     * 
     * @var array
     */
    private $roles = array('USER', 'ADMIN');


    /**
     * Constructor
     *
     * @param \Doctrine\ORM\EntityManager $em   The entity manager
     * @param array                       $hmac Hmac settings
     * 
     * @return void
     */
    public function __construct($em, $hmac = array())
    {
        $this->em = $em;
        
        if (isset($hmac['hmac_roles'])) {
            $this->roles = $hmac['hmac_roles'];
        }
    }
    
    /**
     * Return the list of apiUsers
     * 
     * @return array
     */
    public function getApiUsers()
    {
        $users = array();
        $usersEm = $this->_getApiUserRepo()->findAll();
        foreach ($usersEm as $user) {
            $users[] = $user->toArray();
        }
        
        return $users;
    }
    
    /**
     * Return a single api user
     * 
     * @param string $apikey ApiKey
     * 
     * @throws APIException
     * 
     * @return array
     */
    public function getApiUser($apikey)
    {
        return $this->_getApiUser($apikey)->toArray();
    }
    
    /**
     * ApiUser creation
     * 
     * @param string $key   Key
     * @param string $email Email
     * 
     * @throws APIException
     * 
     * @return void
     */
    public function createUser($key, $email)
    {
        if ($this->_getApiUserRepo()->findOneByApikey($key)) {
            throw new APIException('User already exists', -1, 400);
        }
            
        $user = new \HMACBundle\Entity\ApiUser();
        $user->setApikey($key);
        $user->setApisecret($this->_getNewSecret());
        $user->setEmail($email);
        $user->setEnabled(true);
        $user->addRole('USER');
        
        return $user;
    }
    
    /**
     * Update a given with a given key value parameter set
     * 
     * @param string $apikey Username
     * @param array  $params Key/Val pair of params. Key will be converted into
     * and accessor name to set on found user object.
     * 
     * @return void
     */
    public function updateUser($apikey, array $params)
    {
        $user = $this->_getApiUser($apikey);
        
        if (isset($params['email']) && $params['email']) {
            $user->setEmail($params['email']);
        }
        
        if (isset($params['secret']) && $params['secret']) {
            $user->setSecret($params['secret']);
        }
        
        $this->em->persist($user);
        $this->em->flush();
    }
    
    /**
     * Enable/Disable the api user
     * 
     * @param string  $apikey  ApiKey
     * @param boolean $enabled Enabled
     * 
     * @return void
     */
    public function toggleUser($apikey, $enabled = false)
    {
        $user = $this->_getApiUser($apikey);
        $user->setEnabled($enabled);
        $this->em->persist($user);
        $this->em->flush();
    }
    
    /**
     * Set a role for a given user
     * 
     * @param string $apikey ApiUser
     * @param string $role   Role to add
     * 
     * @throws APIException
     * 
     * @return void
     */
    public function setRole($apikey, $role)
    {
        if (in_array($role, $this->_getAllowedRoles())) {
            $user = $this->_getApiUser($apikey);
            
            if (in_array($role, $user->getRoles())) {
                throw new APIException(
                    'Role already exists: ' . $role, 
                    -1, 
                    400
                );
            }
            
            $user->addRole($role);
            $this->em->persist($user);
            $this->em->flush();
        } else {
            throw new APIException('Invalid role specified: ' . $role, -1, 400);
        }
    }
    
    /**
     * Remove a role from a given user
     * 
     * @param string $apikey ApiUser
     * @param string $role   Role to add    
     * 
     * @throws APIException
     * 
     * @return void
     */
    public function removeRole($apikey, $role)
    {
        $user = $this->_getApiUser($apikey);
        if (in_array($role, $user->getRoles())) {
            $roles = array_flip($user->getRoles());
            unset($roles[$role]);
            $user->setRoles(array_flip($roles));
            $this->em->persist($user);
            $this->em->flush();
        } else {
            throw new APIException('User does not have role: ' . $role, -1, 400);
        }
    }
    
    /**
     * Remove a given user
     * 
     * @param string $apikey ApiUser
     * 
     * @return void
     */
    public function deleteUser($apikey)
    {
        $user = $this->_getApiUser($apikey);
        $this->em->remove($user);
        $this->em->flush();
    }
    
    /**
     * Return a new secret
     * 
     * @return string
     */
    private function _getNewSecret()
    {
        return substr(hash('SHA256', mt_rand()), 0, 16);
    }
    
    /**
     * Get apiuser object
     * 
     * @param string $apikey ApiKey
     * 
     * @throws APIException
     * 
     * @return \HMACBundle\Entity\ApiUser
     */
    private function _getApiUser($apikey)
    {
        $user = $this->_getApiUserRepo()->findOneByApikey($apikey);
        
        if ($user) {
            return $user;
        } else {
            throw new APIException('User not found: ' . $apikey, -1, 404);
        }
    }
    
    /**
     * Return what allowed roles a user can be 
     * 
     * @return array
     */
    private function _getAllowedRoles()
    {
        return $this->roles;
    }
    
    /**
     * Return the api user repo
     * 
     * @return \Doctrine\ORM\EntityRepository
     */
    private function _getApiUserRepo()
    {
        return $this->em->getRepository(
            'HMACBundle:ApiUser'
        );
    }
}
