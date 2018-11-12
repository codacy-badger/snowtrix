<?php

namespace TricksBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class FormHandler
{

    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var Session
     */
    protected $session;

    public function __construct(EntityManagerInterface $em, SessionInterface $session)
    {
        $this->em = $em;
        $this->session = $session;
    }

}