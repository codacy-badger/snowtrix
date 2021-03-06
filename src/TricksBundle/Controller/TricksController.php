<?php

namespace TricksBundle\Controller;

use CoreBundle\Form\Handler\CommentFormHandler;
use CoreBundle\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use TricksBundle\Entity\Trick;
use TricksBundle\Entity\TrickComment;
use TricksBundle\Form\Handler\TrickEditFormHandler;
use TricksBundle\Form\Handler\TrickFormHandler;
use TricksBundle\Form\TrickEditType;
use TricksBundle\Form\TrickType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TricksController extends Controller
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @Route("/trick/{slug}", name="trick_view")
     */
    public function viewAction(Request $request, Trick $trick, CommentFormHandler $commentFormhandler)
    {
        // Comments are paginated. commentsPage param could be passed via GET
        $commentsPage = $request->query->getInt('commentsPage', 1);

        // Comment add form
        $trickComment = new TrickComment();
        $trickComment->setTrick($trick);
        $commentForm = $this->createForm(CommentType::class, $trickComment);

        if ($commentFormhandler->handle($request, $commentForm, $this->getUser())) {
            return $this->redirectToRoute('trick_view', ['slug' => $trick->getSlug()]);
        }

        // Paginated comments
        $nbCommentsPerPage = 10; // For Comments pagination
        $trickComments = $this->em->getRepository('TricksBundle:TrickComment')->findAllByTrickPaginated($trick, $commentsPage, $nbCommentsPerPage);
        $nbPagesComments = ceil(count($trickComments)/$nbCommentsPerPage);

        return $this->render('snowtrix/tricks/view.html.twig',
            ['trick' => $trick,
                'commentForm' => $commentForm->createView(),
                'trickComments' => $trickComments,
                'commentsPage' => $commentsPage,
                'nbPagesComments' => $nbPagesComments]);
    }

    /**
     * @Route("/add-trick", name="trick_add")
     * @Security("has_role('ROLE_USER')")
     */
    public function addAction(Request $request, TrickFormHandler $trickFormHandler){

        $trick = new Trick();
        $trickForm = $this->createForm(TrickType::class, $trick);

        // If submission has been done with success
        if ($trickFormHandler->handle($request, $trickForm)) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('snowtrix/tricks/add.html.twig',
            ['trickForm' => $trickForm->createView()]);
    }

    /**
     * @Route("/edit-trick/{slug}", name="trick_edit")
     * @Security("has_role('ROLE_USER')")
     */
    public function editAction(Request $request, Trick $trick, TrickEditFormHandler $trickEditFormHandler){

        $trickForm = $this->createForm(TrickEditType::class, $trick);

        // If submission has been done with success
        if($trickEditFormHandler->handle($request, $trickForm)) {
            return $this->redirectToRoute('trick_view', ['slug' => $trick->getSlug()]);
        }

        return $this->render('snowtrix/tricks/edit.html.twig',
            ['trick' => $trick, 'trickForm' => $trickForm->createView()]);
    }

    /**
     * @Route("/delete-trick/{slug}", name="trick_delete")
     * @Security("has_role('ROLE_USER')")
     */
    public function deleteAction(Request $request, Trick $trick) {
        $trickDeleteForm =
            $this->createFormBuilder()
            ->add('deleteTrick', SubmitType::class, ['label' => 'Oui, supprimer la figure'])
            ->getForm();

        $trickDeleteForm->handleRequest($request);

        if($trickDeleteForm->isSubmitted()) {
            if($trickDeleteForm->isValid()) {
                $this->em->remove($trick);
                $this->em->flush();
                $this->addFlash('actionInfoSuccess','La figure "'.$trick->getName().'" a bien été supprimée.');
                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render('snowtrix/tricks/delete.html.twig',
            ['trick' => $trick, 'formDeleteTrick' => $trickDeleteForm->createView()]);
    }
}
