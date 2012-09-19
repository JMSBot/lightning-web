<?php

namespace Lightning\ApiBundle\Tests\Controller;

class ListControllerTest extends ApiControllerTest
{
    public function setUp()
    {
        parent::setUp();

        $account = $this->createAccount();
        $this->createList($account);
    }

    public function testShow()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/lists/1', array(), array(), array(
            'HTTP_ACCOUNT' => 'http://localhost/accounts/1?secret=123',
            'HTTP_ACCEPT' => 'application/json',
        ));

        $this->assertEquals('{"id":1,"title":"Groceries","url":"http:\/\/localhost\/lists\/1"}', $client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
    
}
