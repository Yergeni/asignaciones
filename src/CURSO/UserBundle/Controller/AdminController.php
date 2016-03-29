<?php

namespace CURSO\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class AdminController extends Controller
{
    /**
     * @Route("/admin", name="user_admin")
     */
    public function adminAction()
    {
        return $this->render('CURSOUserBundle:Admin:admin.html.twig', array(
            // ...
        ));
    }

}
