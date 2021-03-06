<?php

namespace UserBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use UserBundle\Entity\User;
use UserBundle\Service\UserAvatarUploader;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\ValidatorBuilderInterface;

class ChangePasswordFormHandler extends FormHandler {

    /**
     * @var ValidatorBuilderInterface
     */
    private $validator;

    public function __construct(EntityManagerInterface $em, SessionInterface $session, ValidatorInterface $validator)
    {
        parent::__construct($em, $session);
        $this->validator = $validator;
    }

    public function handle(Request $request, Form $form, User $user) {
        $form->handleRequest($request);

        // We have to get User's plainPassword constraint.
        $classMetadata = $this->validator->getMetadataFor('UserBundle\Entity\User');

        // Fetching new password datas from request.
        $newPassword = $request->request->get('change_password')['plainPassword']['first'];

        // validate newPlainPassword with User's plainPassword constraints.
        $userPlainPasswordConstraints = $classMetadata->getPropertyMetadata('plainPassword')[0]->getConstraints();
        $violations = $this->validator->validate($newPassword,$userPlainPasswordConstraints,array('change_password'));

        foreach($violations as $violation) {
            $form->addError(new FormError($violation->getMessage()));
        }

        if($form->isSubmitted() && $form->isValid()) {

            /*
             * Finally set user plainPassword and flush, user's plainPassword will be automatically encoded by
             * UserBundle\Doctrine\User\HashPasswordListener.
             */
            $user->setPlainPassword($newPassword);

            $this->em->flush();

            // Flash message : success
            $this->session->getFlashBag()->add(
                'actionInfoSuccess',
                'Merci ! Votre mot de passe a bien été modifié.'
            );

            return true;
        }

        return false;
    }

}