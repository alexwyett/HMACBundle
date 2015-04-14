<?php

namespace HMACBundle\Tests\Controller;

use HMACBundle\Tests\TestBase;

class DefaultControllerTest extends TestBase
{    
    /**
     * Test the hmac debug route
     * 
     * @param string  $method HTTP verb
     * @param array   $params Query params
     * @param boolean $result HMAC Result
     * 
     * @dataProvider hmacProvision
     * 
     * @return void
     */
    public function testHmac($method, $params, $result)
    {
        extract(
            $this->doRequest(
                '/v2/auth/debug',
                $method,
                $params,
                false
            )
        );
        
        $this->assertEquals($result, $json->status);
    }
    
    /**
     * HMAC Provider
     * 
     * @return array
     */
    public function hmacProvision()
    {
        return array(
            array(
                'GET',
                array(
                    'hmacKey' => 'alex',
                    'hmacHash' => '064279a2ab24a54d2d380b3c805350a6fa5a6b65dbd2730c75d429fd198aff66'
                ),
                true
            ),
            array(
                'GET',
                array(
                    'foo' => 'bar',
                    'hmacKey' => 'alex',
                    'hmacHash' => '5b649504d227b1e1e960172fc358ef0950d4a68050ca84efd0dd565420e3a3a6'
                ),
                true
            ),
            array(
                'POST',
                array(
                    'hmacKey' => 'alex',
                    'hmacHash' => '5b649504d227b1e1e960172fc358ef0950d4a68050ca84efd0dd565420e3a3a6',
                    'foo' => 'bar'
                ),
                true
            ),
            array(
                'PUT',
                array(
                    'hmacKey' => 'alex',
                    'hmacHash' => '9b1da3722e19b39c5a892c4691385ef60ae777bc23c27eea9e17376e75cad45f',
                    'foo' => 'bar'
                ),
                true
            ),
            array(
                'DELETE',
                array(
                    'foo' => 'bar',
                    'hmacKey' => 'alex',
                    'hmacHash' => 'e675e05d1341632929248cf370f804eb1e8a7f6f13b2b98313842e4c8dbe1c7e'
                ),
                true
            ),
            array(
                'OPTIONS',
                array(
                    'foo' => 'bar',
                    'hmacKey' => 'alex',
                    'hmacHash' => '67e4b91028ef1ff5a6b4a537e5b64a5235edeea13aac5d086c3e858767a06e88'
                ),
                true
            )
        );
    }
}
