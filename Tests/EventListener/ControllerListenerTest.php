<?php

namespace Wix\BaseBundle\Tests\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Wix\BaseBundle\EventListener\ControllerListener;
use Wix\BaseBundle\Tests\EventListener\Fixture\FooControllerPermissionOwnerAtClass;
use Wix\BaseBundle\Tests\EventListener\Fixture\FooControllerPermissionOwnerAtMethod;
use Wix\BaseBundle\Tests\EventListener\Fixture\FooControllerPermissionsAtClassAndMethod;
use Wix\BaseBundle\Tests\EventListener\Fixture\FooControllerWithoutPermission;

class ControllerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    protected $request;

    public function setUp()
    {
        $this->request = new Request();
    }

    public function tearDown()
    {
        $this->request = null;
    }

    /**
     * @expectedException \Wix\BaseBundle\Exception\AccessDeniedException
     */
    public function testPermissionOwnerAtMethodWitInvalidPermissions()
    {
        $controller = new FooControllerPermissionOwnerAtMethod();

        $event = $this->getFilterControllerEvent(array($controller, 'barAction'), $this->request);
        $listener = $this->getControllerListener('NOT OWNER');

        $listener->onKernelController($event);
    }

    public function testPermissionOwnerAtMethodWitValidPermissions()
    {
        $controller = new FooControllerPermissionOwnerAtMethod();

        $event = $this->getFilterControllerEvent(array($controller, 'barAction'), $this->request);
        $listener = $this->getControllerListener('OWNER');

        $listener->onKernelController($event);
    }

    /**
     * @expectedException \Wix\BaseBundle\Exception\AccessDeniedException
     */
    public function testPermissionOwnerAtClassWitInvalidPermissions()
    {
        $controller = new FooControllerPermissionOwnerAtClass();

        $event = $this->getFilterControllerEvent(array($controller, 'barAction'), $this->request);
        $listener = $this->getControllerListener('NOT OWNER');

        $listener->onKernelController($event);
    }

    public function testPermissionOwnerAtClassWitValidPermissions()
    {
        $controller = new FooControllerPermissionOwnerAtClass();

        $event = $this->getFilterControllerEvent(array($controller, 'barAction'), $this->request);
        $listener = $this->getControllerListener('OWNER');

        $listener->onKernelController($event);
    }

    public function testWithoutPermission()
    {
        $controller = new FooControllerWithoutPermission();

        $event = $this->getFilterControllerEvent(array($controller, 'barAction'), $this->request);
        $listener = $this->getControllerListener('NOT OWNER');

        $listener->onKernelController($event);
    }

    public function testPermissionAtClassAndMethod()
    {
        $controller = new FooControllerPermissionsAtClassAndMethod();

        $event = $this->getFilterControllerEvent(array($controller, 'barAction'), $this->request);
        $listener = $this->getControllerListener('NOT OWNER');
        $listener->onKernelController($event);

        $listener = $this->getControllerListener('OWNER');
        $listener->onKernelController($event);
    }

    /**
     * @param $owner
     * @return ControllerListener
     */
    protected function getControllerListener($owner)
    {
        $instance = $this->getMockBuilder('Wix\BaseBundle\Instance\Instance')
          ->disableOriginalConstructor()
          ->getMock();

        $instance->expects($this->any())->method('getPermissions')->will($this->returnValue($owner));

        $decoder = $this->getMockBuilder('Wix\BaseBundle\Instance\Decoder')
          ->disableOriginalConstructor()
          ->getMock();

        $decoder->expects($this->any())->method('parse')->will($this->returnValue($instance));

        $listener = new ControllerListener(new AnnotationReader(), $decoder);

        return $listener;
    }

    /**
     * @param $controller
     * @param Request $request
     * @return FilterControllerEvent
     */
    protected function getFilterControllerEvent($controller, Request $request)
    {
        $mockKernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', array('', ''));

        return new FilterControllerEvent($mockKernel, $controller, $request, HttpKernelInterface::MASTER_REQUEST);
    }
}
