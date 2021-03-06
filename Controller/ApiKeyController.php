<?php

namespace HMACBundle\Controller;
use HMACBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use HMACBundle\Annotations as Annotation;
use HMACBundle\Annotations\HMAC;

/**
 * ApiKey Crud controller
 *
 * @category  Controller
 * @package   TOCC
 * @author    Alex Wyett <alex@wyett.co.uk>
 * @copyright 2014 Alex Wyett
 * @license   All rights reserved
 * @link      http://www.wyett.co.uk
 */
class ApiKeyController extends DefaultController
{
    /**
     * Helper route for debugging hmac requests
     * 
     * @Route("/debug", name="debug_apiuser", defaults={"_format" = "_json", "_filterable" = true})
     * @Method({"GET", "POST", "PUT", "DELETE", "OPTIONS"})
     * @HMAC(public=true)
     * 
     * @return array
     */
    public function debugAction()
    {
        $params = Annotation\HMAC::getHashParams(
            $this->getRequest()
        );
        
        // Save hash for later
        $hash = $params['hmacHash'];
        
        // Unset the hash
        unset($params['hmacHash']);
        
        // Formulate the correct hashing array
        $hashParams = $params;
        
        // Add correct hash
        $correctHash = Annotation\HMAC::hash(
            $hashParams
        );
        
        if (isset($params['APIKEY'])) {
            unset($hashParams['hash']);
            unset($hashParams['hmacKey']);
            unset($hashParams['hmacHash']);
            $hashParams['APISECRET'] = $params['secret'];
            unset($hashParams['secret']);
        } else {
            unset($hashParams['hmacHash']);
        }
        
        return array(
            'request' => $this->getRequest()->getUri(),
            'method' => $this->getRequest()->getRealMethod(),
            'hash' => $hash,
            'correctHash' => $correctHash,
            'status' => ($hash == $correctHash),
            'hashParams' => $hashParams
        );
    }
    
    /**
     * List ApiUsers function
     * 
     * @Route("/apiuser", name="list_apiusers", defaults={"_format" = "_json", "_filterable" = true})
     * @Method("GET")
     * @HMAC(public=false, roles="ADMIN")
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listApiUsersAction()
    {
        return $this->_getUserService()->getApiUsers();
    }
    
    /**
     * List ApiUsers function
     * 
     * @Route("/apiuser/{apikey}", name="view_apiuser", defaults={"_format" = "_json"})
     * @Method("GET")
     * @HMAC(public=false, roles="ADMIN")
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listApiUserAction($apikey)
    {
        return $this->_getUserService()->getApiUser($apikey);
    }
    
    /**
     * Create an api user
     * 
     * @Route("/apiuser", name="create_apiuser")
     * @Method("POST")
     * @HMAC(public=false, roles="ADMIN")
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createApiUserAction()
    {
        if (!$this->getFromRequest('key', null) 
            || !$this->getFromRequest('email', null)
        ) {
            throw new \HMACBundle\Exceptions\APIException(
                'Key/Email not supplied',
                -1,
                400
            );
        }
        
        $user = $this->_getUserService()->createUser(
            $this->getFromRequest('key', null), 
            $this->getFromRequest('email', null)
        );
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        
        return $this->createdResponse(
            $this->generateUrl(
                'view_apiuser', 
                array(
                    'apikey' => $user->getApikey()
                )
            )
        );
    }
    
    /**
     * Remove an api user
     * 
     * @Route("/apiuser/{apikey}", name="delete_apiuser")
     * @Method("DELETE")
     * @HMAC(public=false, roles="ADMIN")
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteApiUserAction($apikey)
    {
        $this->_getUserService()->deleteUser($apikey);
        return $this->okResponse();
    }
    
    /**
     * Update an api user
     * 
     * @Route("/apiuser/{apikey}", name="update_apiuser")
     * @Method("PUT")
     * @HMAC(public=false, roles="ADMIN")
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateApiUserAction($apikey)
    {
        $this->_getUserService()->updateUser(
            $apikey, 
            array(
                'email' => $this->getFromRequest('email', null),
                'apisecret' => $this->getFromRequest('secret', null)
            )
        );
        
        return $this->okResponse();
    }
    
    /**
     * Enable an api user
     * 
     * @Route("/apiuser/{apikey}/enable", name="enable_apiuser")
     * @Method("POST")
     * @HMAC(public=false, roles="ADMIN")
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function enableApiUserAction($apikey)
    {
        $this->_getUserService()->toggleUser($apikey, true);
        return $this->okResponse();
    }
    
    /**
     * Disable an api user
     * 
     * @Route("/apiuser/{apikey}/disable", name="disable_apiuser")
     * @Method("POST")
     * @HMAC(public=false, roles="ADMIN")
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function disableApiUserAction($apikey)
    {
        $this->_getUserService()->toggleUser($apikey, false);
        return $this->okResponse();
    }
    
    /**
     * Add a role to an api user
     * 
     * @Route("/apiuser/{apikey}/role/{role}", name="add_apiuserrole")
     * @Method("PUT")
     * @HMAC(public=false, roles="ADMIN")
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addApiUserRoleAction($apikey, $role)
    {
        $this->_getUserService()->setRole($apikey, $role);
        return $this->okResponse();
    }
    
    /**
     * Delete a role to an api user
     * 
     * @Route("/apiuser/{apikey}/role/{role}", name="delete_apiuserrole")
     * @Method("DELETE")
     * @HMAC(public=false, roles="ADMIN")
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteApiUserRoleAction($apikey, $role)
    {
        $this->_getUserService()->removeRole($apikey, $role);
        return $this->okResponse();
    }
    
    /**
     * Return the user service
     * 
     * @return \HMACBundle\Services\ApiUserService
     */
    private function _getUserService()
    {
        return $this->get('HMAC_apiuser_service');
    }
}