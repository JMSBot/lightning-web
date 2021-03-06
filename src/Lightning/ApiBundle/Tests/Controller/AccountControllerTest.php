<?php

namespace Lightning\ApiBundle\Tests\Controller;

class AccountControllerTest extends ApiControllerTest
{
    public function testCreate()
    {
        $random = $this->getMock('Lightning\ApiBundle\Service\Random');
        $random->expects($this->any())
            ->method('code')
            ->will($this->returnValue('abc'));
        $random->expects($this->any())
            ->method('secret')
            ->will($this->returnValue('123'));
        static::$kernel->getContainer()->set('lightning.api_bundle.service.random', $random);

        $crawler = $this->client->request('POST', '/accounts');

        $response = $this->client->getResponse();
        $this->assertEquals(
            '{"id":1,"url":"http:\/\/localhost\/accounts\/1","short_url":"http:\/\/localhost\/1\/abc","account":"http:\/\/localhost\/accounts\/1?secret=123","lists_url":"http:\/\/localhost\/accounts\/1\/lists"}',
            $response->getContent()
        );
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testShow()
    {
        $this->createAccount();

        $crawler = $this->client->request(
            'GET',
            '/accounts/1',
            array(),
            array(),
            array(
                'HTTP_ACCOUNT' => 'http://localhost/accounts/1?secret=123',
                'HTTP_ACCEPT' => 'application/json',
            )
        );

        $response = $this->client->getResponse();
        $this->assertEquals(
            '{"id":1,"url":"http:\/\/localhost\/accounts\/1","short_url":"http:\/\/localhost\/1\/abc","lists_url":"http:\/\/localhost\/accounts\/1\/lists"}',
            $response->getContent()
        );
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShowNoAccount()
    {
        $this->createAccount();

        $crawler = $this->client->request(
            'GET',
            '/accounts/1',
            array(),
            array(),
            array(
                'HTTP_ACCEPT' => 'application/json',
            )
        );

        $response = $this->client->getResponse();
        $this->assertEquals(
            '{"error":{"code":401,"message":"Account header not found."}}',
            trim($response->getContent())
        );
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testShowWrongSecret()
    {
        $this->createAccount();

        $crawler = $this->client->request(
            'GET',
            '/accounts/1',
            array(),
            array(),
            array(
                'HTTP_ACCOUNT' => 'http://localhost/accounts/1?secret=987',
                'HTTP_ACCEPT' => 'application/json',
            )
        );

        $response = $this->client->getResponse();
        $this->assertEquals(
            '{"error":{"code":403,"message":"Account header authentication failed."}}',
            trim($response->getContent())
        );
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testShowWrongId()
    {
        $this->createAccount();

        $crawler = $this->client->request(
            'GET',
            '/accounts/999',
            array(),
            array(),
            array(
                'HTTP_ACCOUNT' => 'http://localhost/accounts/1?secret=123',
                'HTTP_ACCEPT' => 'application/json',
            )
        );

        $response = $this->client->getResponse();
        $this->assertEquals(
            '{"error":{"code":404,"message":"No account found for id 999."}}',
            trim($response->getContent())
        );
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testShowWrongAccount()
    {
        $this->createAccount();
        $this->createAccount();

        $crawler = $this->client->request(
            'GET',
            '/accounts/2',
            array(),
            array(),
            array(
                'HTTP_ACCOUNT' => 'http://localhost/accounts/1?secret=123',
                'HTTP_ACCEPT' => 'application/json',
            )
        );

        $response = $this->client->getResponse();
        $this->assertEquals(
            '{"error":{"code":403,"message":"Account 2 doesn\'t match authenticated account."}}',
            trim($response->getContent())
        );
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testDeviceToken()
    {
        $this->createAccount();

        $airship = $this->getMockBuilder('Lightning\ApiBundle\Service\UrbanAirship')
            ->disableOriginalConstructor()
            ->getMock();
        $airship->expects($this->once())
            ->method('register')
            ->with('ABC123', 'http://localhost/accounts/1');
        static::$kernel->getContainer()->set('lightning.api_bundle.service.urban_airship', $airship);

        $crawler = $this->client->request(
            'PUT',
            '/accounts/1/device_tokens/ABC123',
            array(),
            array(),
            array(
                'HTTP_ACCOUNT' => 'http://localhost/accounts/1?secret=123',
                'HTTP_ACCEPT' => 'application/json',
            )
        );

        $response = $this->client->getResponse();
        $this->assertEquals('', $response->getContent());
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testDeviceTokenException()
    {
        $this->createAccount();

        $airship = $this->getMockBuilder('Lightning\ApiBundle\Service\UrbanAirship')
            ->disableOriginalConstructor()
            ->getMock();
        $airship->expects($this->once())
            ->method('register')
            ->with('ABC123', 'http://localhost/accounts/1')
            ->will($this->throwException(new \RuntimeException('Internal error')));
        static::$kernel->getContainer()->set('lightning.api_bundle.service.urban_airship', $airship);

        $crawler = $this->client->request(
            'PUT',
            '/accounts/1/device_tokens/ABC123',
            array(),
            array(),
            array(
                'HTTP_ACCOUNT' => 'http://localhost/accounts/1?secret=123',
                'HTTP_ACCEPT' => 'application/json',
            )
        );

        $response = $this->client->getResponse();
        $this->assertEquals(
            '{"error":{"code":500,"message":"Internal error"}}',
            trim($response->getContent())
        );
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testDeviceTokenWrongId()
    {
        $this->createAccount();

        $crawler = $this->client->request(
            'PUT',
            '/accounts/999/device_tokens/ABC123',
            array(),
            array(),
            array(
                'HTTP_ACCOUNT' => 'http://localhost/accounts/1?secret=123',
                'HTTP_ACCEPT' => 'application/json',
            )
        );

        $response = $this->client->getResponse();
        $this->assertEquals(
            '{"error":{"code":404,"message":"No account found for id 999."}}',
            trim($response->getContent())
        );
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testDeviceTokenWrongAccount()
    {
        $this->createAccount();
        $this->createAccount();

        $crawler = $this->client->request(
            'PUT',
            '/accounts/2/device_tokens/ABC123',
            array(),
            array(),
            array(
                'HTTP_ACCOUNT' => 'http://localhost/accounts/1?secret=123',
                'HTTP_ACCEPT' => 'application/json',
            )
        );

        $response = $this->client->getResponse();
        $this->assertEquals(
            '{"error":{"code":403,"message":"Account 2 doesn\'t match authenticated account."}}',
            trim($response->getContent())
        );
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertEquals(403, $response->getStatusCode());
    }
}
