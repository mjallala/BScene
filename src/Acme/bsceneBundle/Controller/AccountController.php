<?php

namespace Acme\bsceneBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Acme\bsceneBundle\Entity\Account;
use Acme\bsceneBundle\Form\AccountType;
use Acme\bsceneBundle\Entity\Organization;
use Acme\bsceneBundle\Entity\Categories;
use Acme\bsceneBundle\Form\CategoriesType;

/**
 * Account controller.
 *
 */
class AccountController extends Controller {

    /**
     * Lists all Account entities.
     *
     */
    public function indexAction() {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AcmebsceneBundle:Account')->findAll();

        return $this->render('AcmebsceneBundle:Account:index.html.twig', array(
                    'entities' => $entities,
        ));
    }

    /**
     * Creates a new Account entity.
     * added the create organization first - doaa elfayoumi - 23.03.2015 
     * added the encryption for the password - doaa elfayoumi -  24.03.2015
     */
    public function createAction(Request $request) {
        //if the organization is filled create it first
        if ($request->get("orgName") != "") {
            $orgEntity = new Organization();
            $orgEntity->setName($request->get("orgName"));
            $orgEntity->setWebsite($request->get("orgUrl"));
            $em = $this->getDoctrine()->getManager();
            $em->persist($orgEntity);
            $em->flush();
        }


        $entity = new Account();
        $hash = md5(rand(0, 1000)); // Generate random 32 character hash and assign it to a local variable.

        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        $entity->setMemberSince(new \DateTime());
        $entity->setVerificationHash($hash);

        //encrypt password 
        $plainPassword = $entity->getPassword();
        $encoder = $this->container->get('security.password_encoder');
        $encoded = $encoder->encodePassword($entity, $plainPassword);
        $entity->setPassword($encoded);



        //set the created organization on the account entity
        if ($request->get("orgName") != "") {
            $entity->setOrganization($orgEntity);
        }

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->sendVerificationAction($entity->getId());

            return $this->render('AcmebsceneBundle:Account:VerificationSent.html.twig');
        }

        return $this->render('AcmebsceneBundle:Account:new.html.twig', array(
                    'entity' => $entity,
                    'form' => $form->createView(),
        ));
    }

    /*
     * Send verification e-mails
     * Created by: Victoria Betts, April 2, 2015
     */

    private function sendVerificationAction($id) {

        $em = $this->getDoctrine()->getManager();

        $email = $em->createQuery("SELECT a.email FROM \Acme\bsceneBundle\Entity\Account a WHERE a.id = :id")->setParameter('id', $id);
        $userEmail = $email->getResult();

        $firstName = $em->createQuery("SELECT a.firstName FROM \Acme\bsceneBundle\Entity\Account a WHERE a.id = :id")->setParameter('id', $id);
        $userFirstName = $firstName->getResult();

        $hash = $em->createQuery("SELECT a.verificationHash FROM \Acme\bsceneBundle\Entity\Account a WHERE a.id = :id")->setParameter('id', $id);
        $userHash = $hash->getResult();

        $userEmail = implode($userEmail[0]);
        $userFirstName = implode($userFirstName[0]);
        $userId = $id;
        $userHash = implode($userHash[0]);

        $emailLink = 'http:\/\/localhost\/BScene\/web\/app_dev.php\/account\/validated\/' . $userId . '\/' . $userHash;

        //$mailer = $this->get('mailer');

        $transport = \Swift_SmtpTransport::newInstance('localhost', 25);
        $mailer = \Swift_Mailer::newInstance($transport);

        $message = \Swift_Message::newInstance()
                ->setSubject('B-Scene Registration!')
                ->setFrom('bscenenetwork@gmail.com')
                ->setTo($userEmail)
                ->setBody($userFirstName . ', <br> Thank you for signing up for B-Scene! '
                . 'Please click the below link to verify your registration: <br>'
                . $emailLink . ' <br>'
                . 'Regards, <br> B-Scene Team', 'text/html');
        $result = $mailer->send($message);

        echo($result);
    }

    /**
     * Creates a form to create a Account entity.
     *
     * @param Account $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Account $entity) {
        $form = $this->createForm(new AccountType(), $entity, array(
            'action' => $this->generateUrl('account_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Account entity.
     *
     */
    public function newAction() {
        $entity = new Account();
        $form = $this->createCreateForm($entity);

        return $this->render('AcmebsceneBundle:Account:new.html.twig', array(
                    'entity' => $entity,
                    'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Account entity.
     *
     */
    public function showAction(Request $request, $id) {


        $em = $this->getDoctrine()->getManager();


        $entity = $em->getRepository('AcmebsceneBundle:Account')->find($id);
        $eventList = $this->getUpcomingMeetingList($id);
        $pastEventList = $this->getPastMeetingList($id);
        $eventCount = \Count($eventList);
        $pastEventCount = \Count($pastEventList);
        $noEventsMsg = "There are no upcoming events posted by this user";
        $noPastEventsMsg = "There are no past events for this user";

        $organization = $this->getOrgUrl($id);


        if ($eventCount > 3) {
            $eventList = array_slice($eventList, 0, 3);
        }
        if ($pastEventCount > 3) {
            $pastEventList = array_slice($pastEventList, 0, 3);
        }


        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Account entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AcmebsceneBundle:Account:show.html.twig', array(
                    'entity' => $entity,
                    'delete_form' => $deleteForm->createView(),
                    'upcoming' => $eventList,
                    'past' => $pastEventList,
                    'noEventsMsg' => $noEventsMsg,
                    'noPastEventsMsg' => $noPastEventsMsg,
                    'eventCount' => $eventCount,
                    'pastEventCount' => $pastEventCount,
                    'organization' => $organization,
        ));
    }

    /**
     * Displays a form to edit an existing Account entity.
     *
     */
    public function editAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AcmebsceneBundle:Account')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Account entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('AcmebsceneBundle:Account:edit.html.twig', array(
                    'entity' => $entity,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Account entity.
     *
     * @param Account $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createEditForm(Account $entity) {
        $form = $this->createForm(new AccountType(), $entity, array(
            'action' => $this->generateUrl('account_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Account entity.
     *
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AcmebsceneBundle:Account')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Account entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('account_edit', array('id' => $id)));
        }

        return $this->render('AcmebsceneBundle:Account:edit.html.twig', array(
                    'entity' => $entity,
                    'edit_form' => $editForm->createView(),
                    'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Account entity.
     *
     */
    public function deleteAction(Request $request, $id) {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AcmebsceneBundle:Account')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Account entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('account'));
    }

    /**
     * Creates a form to delete a Account entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder()
                        ->setAction($this->generateUrl('account_delete', array('id' => $id)))
                        ->setMethod('DELETE')
                        ->add('submit', 'submit', array(
                            'label' => 'Delete Account',
                            'attr' => array('class' => 'btn btn-danger')))
                        ->getForm()
        ;
    }

    /**
     * Get upcoming events for the profile page
     *
     * @param $id
     *
     * @return array $eventList
     */
    private function getUpcomingMeetingList($id) {
        $currentDate = new \DateTime();

        $em = $this->getDoctrine()->getManager();

        $q = $em->createQuery("SELECT e "
                        . "FROM \Acme\bsceneBundle\Entity\Meeting e "
                        . "WHERE e.account = '$id' AND e.date >= :date "
                        . "ORDER BY e.date ASC")->setParameter('date', $currentDate);
        $eventList = $q->getArrayResult();

        return $eventList;
    }

    private function getPastMeetingList($id) {
        $currentDate = new \DateTime();

        $em = $this->getDoctrine()->getManager();

        $q = $em->createQuery("SELECT e "
                        . "FROM \Acme\bsceneBundle\Entity\Meeting e "
                        . "WHERE e.account = :id AND e.date < :date "
                        . "ORDER BY e.date ASC")->setParameters(array('date' => $currentDate, 'id' => $id));
        $pastEventList = $q->getArrayResult();

        return $pastEventList;
    }

    private function getOrgUrl($id) {
        $em = $this->getDoctrine()->getManager();

        $qA = $em->createQuery("SELECT IDENTITY(a.organization) FROM \Acme\bsceneBundle\Entity\Account a WHERE a.id = :id")->setParameter('id', $id);
        $userOrganization = $qA->getResult();

        $userOrgId = implode($userOrganization[0]);

        $qB = $em->createQuery("SELECT o.website FROM \Acme\bsceneBundle\Entity\Organization o WHERE o.id = '$userOrgId'");
        $userOrganizationSite = $qB->getResult();
        $userOrganizationSite = implode($userOrganizationSite[0]);


        return $userOrganizationSite;
    }

    /*
     * Show subscription form
     */

    public function subscribeAction() {
        $em = $this->getDoctrine()->getManager();

        $q = $em->createQuery("SELECT c FROM \Acme\bsceneBundle\Entity\Categories c");
        $categories = $q->getResult();

        return $this->render('AcmebsceneBundle:Account:subscribe.html.twig', array(
                    'categories' => $categories,
        ));
    }

    /*
     * Save new subscription details
     * Incomplete - not functional yet
     * Victoria Betts
     */

    public function newSubscriptionAction(Request $request) {

        $em = $this->getDoctrine()->getManager();

        $q = $em->createQuery("SELECT c.id, c.name FROM \Acme\bsceneBundle\Entity\Categories c");
        $categories = $q->getArrayResult();
        
        $categoryList = array();
        $subscribeList = array();
        //Create array from DB results to be used in a for loop
        for ($i = 0; $i <= count($categories) - 1; $i++) {
            $category = $categories[$i]['id'];
            array_push($categoryList, $category);
        }
        
        /*
         * Loop through IDs, check each time if it is checked (not null). If not null, add to array
         */

        foreach ($categoryList as $categoryId) {
            $userSelection = $this->get('request')->request->get($categoryId);
            if ($userSelection != null) {
                array_push($subscribeList, $categoryId);
            }
        }
    
        $userId = $request->getSession()->get("memberId");
        return $this->render('AcmebsceneBundle:Account:subscribe.html.twig', array(
                    'categories' => $categories,
        ));
    }

    /*
     * Show 'e-mail validated' page with links to subscription and profile
     */

    public function emailValidatedAction() {
        //$id = 1;
        return $this->render('AcmebsceneBundle:Account:emailValidated.html.twig', array('id' => $id,));
    }

}
