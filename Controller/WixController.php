<?php

namespace Wix\FrameworkBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;
use Wix\DemoBundle\Document\ApplicationUser;
use Wix\FrameworkBundle\Configuration\Permission;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Wix\FrameworkComponent\Instance\Instance;
use Wix\FrameworkBundle\Document\User;
use Wix\FrameworkBundle\Exception\MissingParametersException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class WixController extends Controller
{
    /**
     * Implement return your doc type (e.g. WixFrameworkBundle:User), please make sure your doc inherits WixFrameworkBundle:User
     * @return string
     */
    abstract protected function getDocumentType();

    /**
     * Implement return your doc class (full path) (e.g. Wix\FrameworkBundle\Document\User), please make sure your doc inherits WixFrameworkBundle:User
     * @return string
     */
    abstract protected function getDocumentClass();

    /**
     * @return Instance
     */
    protected function getInstance()
    {
        return $this->get('wix_framework.instance_decoder')->parse(
            $this->getRequest()->get('instance')
        );
    }

    /**
     * @return DocumentManager
     */
    protected function getDocumentManager()
    {
        return $this->get('doctrine.odm.mongodb.document_manager');
    }

    /**
     * @param bool $full
     * @throws MissingParametersException
     * @return mixed
     */
    protected function getComponentId($full = false)
    {
        $query = $this->getRequest()->query;

        $componentId = $query->has('origCompId') ? $query->get('origCompId') : $query->get('compId');

        if ($componentId === null) {
            throw new MissingParametersException('Could not find a component id (originCompId or compId query string parameter).');
        }

        if ($full === false) {
            $componentId = preg_replace("/^(TPWdgt|TPSttngs|TPSctn)/", "", $componentId);
        }

        return $componentId;
    }

    /**
     *
     *
     * @return User
     */
    protected function getUserDocument()
    {
        $componentId = $this->getComponentId();
        $instanceId = $this->getInstance()->getInstanceId();

        $user = $this->getRepository($this->getDocumentType())
          ->findOneBy(array(
                  'instanceId' => $instanceId,
                  'componentId' => $componentId,
              ));

        if ($user === null) {
            $class = $this->getDocumentClass();
            $user = new $class($instanceId, $componentId);
        }

        return $user;
    }

    /**
     * @param $class
     * @return DocumentRepository
     */
    protected function getRepository($class)
    {
        return $this->getDocumentManager()->getRepository($class);
    }

    /**
     * @param $user
     * @return User
     */
    protected function updateUserDoc($user) {
        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush($user);

        return $user;
    }

    /**
     * Serializes the object and returns JSON response
     *
     * @param $object
     * @return JsonResponse
     */
    protected function jsonResponse($object)
    {
        return new JsonResponse($this->getSerializer()->normalize($object, 'json'));
    }

    /**
     * Returns GetSetMethod JSON serializer object
     *
     * @return Serializer
     */
    protected function getSerializer()
    {
        return new Serializer(
            array(new GetSetMethodNormalizer()),
            array(new JsonEncoder())
        );
    }
}
