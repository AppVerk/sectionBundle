<?php

namespace AppVerk\SectionBundle\Controller;

use AppVerk\Components\Controller\LanguageAccessControllerInterface;
use AppVerk\SectionBundle\Doctrine\SectionManager;
use AppVerk\SectionBundle\Entity\Section;
use AppVerk\SectionBundle\Form\Handler\SectionDefaultEditFormHandler;
use AppVerk\SectionBundle\Form\Handler\SectionFormHandler;
use AppVerk\SectionBundle\Form\Type\SectionDefaultEditFormType;
use AppVerk\SectionBundle\Form\Type\SectionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/section")
 */
class SectionController extends BaseController implements LanguageAccessControllerInterface
{
    /**
     * @Route("/create/{type}/{lang}", name="section_create")
     * @Method({"GET", "POST"})
     */
    public function createSectionAction(
        $lang,
        $type,
        SectionFormHandler $sectionFormHandler,
        Request $request
    ) {
        $returnParameters = ['lang' => $lang, 'type' => $type];
        $dataClass = $this->configProvider->getSectionSetting($type, 'model');
        /** @var Section $section */
        $section = new $dataClass();
        $section->setName($type);
        $section->setCurrentLocale($lang);

        if ($request->getMethod() === 'GET') {
            $form = $sectionFormHandler->buildForm(SectionType::class, $section)->getFormView();
            $returnParameters['form'] = $form;
        } else {
            if ($request->getMethod() === 'POST') {
                $form = $sectionFormHandler->buildForm(SectionType::class, $section);

                if (!$sectionFormHandler->process()) {
                    $this->addFlashMessage('danger', $sectionFormHandler->getErrorsAsString());
                } else {
                    $this->addFlashMessage('success', 'section.create.successful');
                }
                $returnParameters['object'] = $section;
                $returnParameters['form'] = $form->getFormView();
            }
        }

        return $this->render($this->configProvider->getSectionView('create'), $returnParameters);
    }

    /**
     * @Route("/edit/{section}/{lang}", name="section_edit")
     * @Method({"POST"})
     */
    public function editSectionAction(
        Section $section,
        $lang,
        SectionDefaultEditFormHandler $sectionFormHandler
    ) {
        $returnParameters = ['lang' => $lang];
        $returnParameters['object'] = $section;

        $section->setCurrentLocale($lang);

        $sectionFormHandler->buildForm(SectionDefaultEditFormType::class, $section);

        if (!$section = $sectionFormHandler->process()) {
            return new Response($sectionFormHandler->getErrorsAsString(), Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'resource_id' => $section->getId()
        ]);
    }

    /**
     * @Route("/delete/{section}/{lang}", name="section_delete")
     * @Method("GET")
     */
    public function deleteAction(
        Section $section,
        $lang = null,
        SectionManager $sectionManager,
        Request $request
    ) {
        if (!$lang) {
            $sectionManager->remove($section);
        }

        if ($lang) {
            $sectionManager->removeTranslation($section, $lang);
        }

        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }
}
