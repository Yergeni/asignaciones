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
     * @Route("/user/list", name="user_list")
     *
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        //$users = $em->getRepository('CURSOUserBundle:User')->findAll();//obtengo todos los objetos de tipo user
        //otra forma es empleando dql
        $dql = 'SELECT u FROM CURSOUserBundle:User u ORDER BY u.id DESC';//la sentencia que luego se usara en la twig
        $users = $em->createQuery($dql);//ejecuto la sentencia
        
        //configurando el paginador
        $paginador = $this->get('knp_paginator');//obtengo el servicio de paginacion
        $paginacion = $paginador->paginate($users, $request->query->getInt('page', 1), 8);//se le pasa la consulta (users), pagina inicial y la cantidad a mostrar por pagina
        
        //formulario para eliminar desde la vista list con ajax
        //Le pasamos el user id, el metodo y la ruta de eliminar
        $createFormAjax = $this->createCustomDeleteForm(':USER_ID', 'DELETE', 'user_delete');
        
        return $this->render('CURSOUserBundle:User:list.html.twig', array(
            //"users" => $users //ya la variable a mostrar el $paginacion pues ya viene con toda la informacion de la lista de usuarios
            'paginacion' => $paginacion,
            'delete_form_ajax' => $createFormAjax->createView()
        ));
    }
    
    //Show user
    /**
     * 
     * @Route("/user/view/{id}", name="user_view")
     * 
     */
    public function viewAction(User $user)
    {
        // $deleteForm = $this->createDeleteForm($user);
        
        // usamos el formulario creado por nosotros
        $deleteForm = $this->createCustomDeleteForm($user->getId(), 'DELETE', 'user_delete');
        
        if(!$user)
        {
            //Enviar notificacion de error
            $mensajeException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($mensajeException);
        }
        else{
            return $this->render('CURSOUserBundle:User:view.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
        }
        // $repository = $this->getDoctrine()->getRepository('CURSOUserBundle:User');
        // $user = $repository->find($id);
        
        // return $this->render('CURSOUserBundle:User:view.html.twig', array("user" => $user));
    }
    
    //crear un nuevo usuario
    /**
     * @Route("/user/new", name="user_add")
     * @Method({"POST", "GET"}) 
     */
     public function addAction(Request $request)
     {
        $user = new User();
        $addform = $this->createForm(UserType::class, $user); //metodo que cre el formulario a partir de un objeto type y el objeto de la entidad correspondiente
        $addform->handleRequest($request);
         
        if ($addform->isSubmitted() && $addform->isValid()) { //Si se envio el formulario a trabajarlo!!!
            
            $plainTextPassword = $addform->get('password')->getData(); //obtiene la variable password ingresado en el formulario
            
            //Validar que no se entre password en blanco usando constrains en el controller
            //Esto devulve un arra asociativo
            $errorList = $this->get('validator')->validate($plainTextPassword, new Assert\NotBlank());
            /*$errorList = $this->get('validator')->validate($plainTextPassword, new Assert\Length(array(
                    'min'        => 4,
                    'max'        => 50,
                    'minMessage' => 'Your first name must be at least {{ limit }} characters length',
                    'maxMessage' => 'Your first name cannot be longer than {{ limit }} characters length'
            )));*/
            if(count($errorList) == 0)//si no ahi errores significa que se ingreso un password
            {
                $encoder = $this->container->get('security.password_encoder');       //obtiene el codificador definido en security.yml
                $pass_encoded = $encoder->encodePassword($user, $plainTextPassword); //lo codifica
                $user->setPassword($pass_encoded);                                   //lo setea
                
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                
                //Enviar notificacion de creado
                $mensaje_traducido = $this->get('translator')->trans('The user has been created.');
                $this->addFlash('mensaje', $mensaje_traducido);
    
                return $this->redirectToRoute('user_list');
                // return $this->redirectToRoute('user_view', array('id' => $user->getId()));   
            }
            else //si se ingreso un password en blanco mostrar un error
            {
                $errorMessage = new FormError($errorList[0]->getMessage()); //obtenemos el error
                $addform->get('password')->addError($errorMessage);         //asignamos mensajes al campo password
            }
        }
        //Si no se envio el formulario solo pintarlo 
        return $this->render('CURSOUserBundle:User:add.html.twig', array('form' => $addform->createView())); //metodo createView() pinta el formulario
     }
     
     //Editar un usuario
    /**
     * @Route("/user/edit/{id}", name="user_edit")
     * @Method({"POST", "GET"}) 
     */
     public function editAction(Request $request, User $user)
     {
        // $deleteForm = $this->createDeleteForm($user);
        // usamos el formulario creado por nosotros
        $deleteForm = $this->createCustomDeleteForm($user->getId(), 'DELETE', 'user_delete');
        
        $editform = $this->createForm(UserType::class, $user);//metodo que cre el formulario a partir de un objeto type y el objeto de la entidad correspondiente
        $editform->handleRequest($request);
        
        $em = $this->getDoctrine()->getManager();
         
        if ($editform->isSubmitted() && $editform->isValid()) {
            
            $plainTextPassword = $editform->get('password')->getData(); //obtiene la variable password ingresado en el formulario
            
            //Si el usario edita su password lo encriptamos
            if(!empty($plainTextPassword))
            {
                $encoder = $this->container->get('security.password_encoder');       //obtiene el codificador definido en security.yml
                $pass_encoded = $encoder->encodePassword($user, $plainTextPassword); //lo codifica
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
        //Si no se envio el formulario solo pintarlo 
        return $this->render('CURSOUserBundle:User:edit.html.twig', array(
            'user' => $user,
            'form' => $editform->createView(),         //metodo que pinta el formulario
            'delete_form' => $deleteForm->createView() //variable delete_form enviada a la vista
            ));
     }
     
     /**
     * Deletes a User entity.
     *
     * @Route("/delete/{id}", name="user_delete")
     * @Method({"POST", "DELETE"})
     */
    public function deleteAction(Request $request, User $user)
    {
        $em = $this->getDoctrine()->getManager();
        
        if(!$user)
        {
            //Enviar notificacion de error
            $mensajeException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($mensajeException);
        }
        //obtenemos la cantidad de usuarios actuales
        $allUsers = $em->getRepository('CURSOUserBundle:User')->findAll();
        $countUsers = count($allUsers);
        
        // $form = $this->createDeleteForm($user);
        
        // creamos nuestra custom form delete de envio por ajax parametros user id, Method DELETE, ruta delete_user
        $form = $this->createCustomDeleteForm($user->getId(), 'DELETE', 'user_delete');
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            //Preguntamos si la peticion vino por ajax
            if($request->isXMLHttpRequest())
            {
                //creamos un metodo reutilizable se le pasa el ROLE (para verificar que tenga permisos), el EntityManager y el usuario a eliminar
                $ajax_response = $this->deleteUser($user->getRole(), $em, $user);
                
                // Creamos un Response para enviarlo
                return new Response(
                    json_encode(array(
                        'removed' => $ajax_response['removed'], //variable bool enviada si se elimino o no el usuario
                        'message_ajax' => $ajax_response['message'], //mensaje enviado
                        'countUsers' => $countUsers)),          //cantidad de usuarios
                    200, //Estado de la respuesta ok
                    array('Content-type' => 'application/json') //tipo de contenido
                );
            }
            // $em->remove($user);
            // $em->flush();
                
            // //Enviar notificacion de creado
            // $mensaje_traducido = $this->get('translator')->trans('The user has been deleted.');
            
            //como deberiamos hacer lo mismo que es chequear el rol, eliminar y enviar mensajes usamos el metodo personal creado deleteUser
            $result = $this->deleteUser($user->getRole(), $em, $user);
            // $this->addFlash('mensaje', $mensaje_traducido);
            $this->addFlash($result['alert'], $result['message']);
    
            return $this->redirectToRoute('user_list');
        }
    }
    
    //Implementacion de la funcion ajax reutilizable declarada en el metodo de deleteAction
    private function deleteUser($role, $em, $user)
    {
        if($role == 'ROLE_USER'){ //Si el usuario a eliminar es de tipo ROLE_USER entonces se elimina
            $em->remove($user);
            $em->flush();
            
            //enviamos el mensaje de eliminacion
            $message = $this->get('translator')->trans('The user has been deleted.');
            $removed = 1; //para indicarnos que el usuario a sido eliminado
            $alert = 'mensaje';
        }
        elseif($role == 'ROLE_ADMIN'){ //Si el usuario es de tipo ROLE_ADMIN no se puede eliminar
            $message = $this->get('translator')->trans('The user could no be deleted.');
            $removed = 0; //para indicarnos que el usuario NO a sido eliminado
            $alert = 'error';
        }
        //Se envia un array con la variable removed en 0 o 1 en dependencia si se elimino o no
        //El mensaje y la alerta
        return array('removed' => $removed, 'message' => $message, 'alert' => $alert);
    }

    /**
     * Creates a form to delete a User entity.
     *
     * @param User $user The User entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    // private function createDeleteForm(User $user)
    // {
    //     return $this->createFormBuilder()
    //         ->setAction($this->generateUrl('user_delete', array('id' => $user->getId())))
    //         //->setMethod('POST')
    //         ->setMethod('DELETE')
    //         ->getForm()
    //     ;
    // }
    
    //implementacion del metodo (Formulario) createCustomDeleteForm
    private function createCustomDeleteForm($id, $method, $route)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl($route, array('id' => $id)))
            ->setMethod($method)
            ->getForm();
    }
     
}
