<?php

namespace CURSO\UserBundle\Controller;

//use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use CURSO\UserBundle\Entity\User;
use CURSO\UserBundle\Form\UserType;
use Symfony\Component\Validator\Constraints as Assert; //Validations
use Symfony\Component\Form\FormError;

class UserController extends Controller
{
    /**
     * @Route("/user/index", name="user_index")
     * @Method("GET")
     *
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        //$users = $em->getRepository('CURSOUserBundle:User')->findAll();//obtengo todos los objetos de tipo user
        //otra forma es empleando dql
        $dql = 'SELECT u FROM CURSOUserBundle:User u ORDER BY u.id DESC';//la sentencia que luego se usara en la twig
        $users = $em->createQuery($dql);//ejecuto la sentencia
        
        //configurando el paginador
        $paginador = $this->get('knp_paginator');//obtengo el servicio de paginacion
        $paginacion = $paginador->paginate($users, $request->query->getInt('page', 1), 8);//se le pasa la consulta (users), pagina inicial y la cantidad a mostrar por pagina
        
        return $this->render('CURSOUserBundle:User:index.html.twig', array(
            //"users" => $users //ya la variable a mostrar el $paginacion pues ya viene con toda la informacion de la lista de usuarios
            'paginacion' => $paginacion
        ));
    }
    
    //Show user
    /**
     * 
     * @Route("/user/view/{id}", name="user_view")
     * @Method("GET")
     */
    public function viewAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('CURSOUserBundle:User:view.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
        
        // $repository = $this->getDoctrine()->getRepository('CURSOUserBundle:User');
        // $user = $repository->find($id);
        
        // return $this->render('CURSOUserBundle:User:view.html.twig', array("user" => $user));
    }
    
    //crear un nuevo usuario
    /**
     * @Route("/user/new", name="user_add")
     * @Method({"GET", "POST"}) 
     */
     public function addAction(Request $request)
     {
        $user = new User();
        $addform = $this->createForm(UserType::class, $user);//metodo que cre el formulario a partir de un objeto type y el objeto de la entidad correspondiente
        $addform->handleRequest($request);
         
        if ($addform->isSubmitted() && $addform->isValid()) {
            
            $plainTextPassword = $addform->get('password')->getData();//obtiene la variable password ingresado en el formulario
            
            //Validar que no se entre password en blanco usando constrains en el controller
            $errorList = $this->get('validator')->validate($plainTextPassword, new Assert\NotBlank());
            /*$errorList = $this->get('validator')->validate($plainTextPassword, new Assert\Length(array(
                    'min'        => 4,
                    'max'        => 50,
                    'minMessage' => 'Your first name must be at least {{ limit }} characters length',
                    'maxMessage' => 'Your first name cannot be longer than {{ limit }} characters length'
            )));*/
            if(count($errorList) == 0)//si no ahi errores significa que se ingreso un password
            {
                $encoder = $this->container->get('security.password_encoder');//obtiene el codificador definido en security.yml
                $pass_encoded = $encoder->encodePassword($user, $plainTextPassword);//lo codifica
                $user->setPassword($pass_encoded);//lo setea
                
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                
                //Enviar notificacion de creado
                $mensaje_traducido = $this->get('translator')->trans('The user has been created.');
                $this->addFlash('mensaje', $mensaje_traducido);
    
                return $this->redirectToRoute('user_index');
                // return $this->redirectToRoute('user_view', array('id' => $user->getId()));   
            }
            else //si se ingreso un password rn blanco mostrar un error
            {
                $errorMessage = new FormError($errorList[0]->getMessage());//obtenemos el error
                $addform->get('password')->addError($errorMessage);//asignamos mensajes al campo
            }
        }
         
         return $this->render('CURSOUserBundle:User:add.html.twig', array('form' => $addform->createView()));//metodo que pinta el formulario
     }
     
     //Editar un usuario
    /**
     * @Route("/user/edit/{id}", name="user_edit")
     * @Method({"GET", "POST"}) 
     */
     public function editAction(Request $request, User $user)
     {
        $deleteForm = $this->createDeleteForm($user);
        $editform = $this->createForm(UserType::class, $user);//metodo que cre el formulario a partir de un objeto type y el objeto de la entidad correspondiente
        $editform->handleRequest($request);
        
        $em = $this->getDoctrine()->getManager();
         
        if ($editform->isSubmitted() && $editform->isValid()) {
            
            $plainTextPassword = $editform->get('password')->getData();//obtiene la variable password ingresado en el formulario
            
            //Si el usario edita su password lo encriptamos
            if(!empty($plainTextPassword))
            {
                $encoder = $this->container->get('security.password_encoder');//obtiene el codificador definido en security.yml
                $pass_encoded = $encoder->encodePassword($user, $plainTextPassword);//lo codifica
                $user->setPassword($pass_encoded);//lo setea
            }
            else{
                //si no lo edita entonces lo buscamos y lo voolvemos a incluir
                $query = $em->createQuery(
                    'SELECT u.password
                    FROM CURSOUserBundle:User u
                    WHERE u.id = :id'
                    );
                $query->setParameter('id', $user->getId());
                $current_password = $query->getResult();//obtenermos el resultado de la consulta (esto devuleve un array)
                $user->setPassword($current_password[0]['password']);//lo seteamos tal cual lo tenia
            }
            //Verificar si la edicion del usuario es un admin siempre guardarlo como activo...
            if($editform->get('role')->getData() == 'ROLE_ADMIN'){
                $user->setIsActive(1);
            }
            
            $em->persist($user);
            $em->flush();
            
            //Enviar notificacion de modificado
            $mensaje_traducido = $this->get('translator')->trans('The user has been modified.');
            $this->addFlash('mensaje', $mensaje_traducido);

            return $this->redirectToRoute('user_edit', array('id' => $user->getId()));
            // return $this->redirectToRoute('user_view', array('id' => $user->getId()));
        }
         
         return $this->render('CURSOUserBundle:User:edit.html.twig', array(
             'user' => $user,
             'form' => $editform->createView(),//metodo que pinta el formulario
             'delete_form' => $deleteForm->createView()
             ));
     }
     
     /**
     * Deletes a User entity.
     *
     * @Route("/delete/{id}", name="user_delete")
     * @Method("POST")
     */
    public function deleteAction(Request $request, User $user)
    {
        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }
        
        //Enviar notificacion de creado
        $mensaje_traducido = $this->get('translator')->trans('The user '. $user->getFirstName() .' '. $user->getLastName() .' has been deleted.');
        $this->addFlash('mensaje', $mensaje_traducido);

        return $this->redirectToRoute('user_index');
    }

    /**
     * Creates a form to delete a User entity.
     *
     * @param User $user The User entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', array('id' => $user->getId())))
            ->setMethod('POST')
            ->getForm()
        ;
    }
     
     
     //implementar la creacion del formulario, metodo createCreateForm()
    //  private function createCreateForm(User $entity)//recive como paramatro una entidad en este caso User
    //  {
    //      //$form_user = new UserType();
    //      $form = $this->createForm(UserType::class, $entity, array(
    //          'action' => $this->generateUrl('user_create'),
    //          'method' => 'POST'
    //          ));//obtiene un nuevo objeto de tipo UserType, la entidad pasada por parametro y un array de opciones
             
    //     return $form;
    //  }
     
    //  /**
    //   * 
    //   * @Route("/user/create", name="user_create")
    //   * 
    //   */
    //   public function createAction()
    //   {
          
    //   }
     
    
    // //uso de parametros
    // /**
    //  * @Route("/user/article/{page}", name="user_articles", defaults={"page" = 1}, requirements={"page" = "\d+"})
    //  * 
    //  */
    // public function articlesAction($page = 1)
    // {
    //     return new Response('Pagina de articulo numero '.$page);
    // }
}
