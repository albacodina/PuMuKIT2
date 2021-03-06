<?php

namespace Pumukit\NewAdminBundle\Form\Type;

use Pumukit\NewAdminBundle\Form\Type\Base\TextareaI18nType;
use Pumukit\NewAdminBundle\Form\Type\Base\TextI18nType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventsType extends AbstractType
{
    private $translator;
    private $locale;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->translator = $options['translator'];
        $this->locale = $options['locale'];

        $builder
            ->add('i18n_name', TextI18nType::class,
                  array(
                      'required' => true,
                      'label' => $this->translator->trans('Title', array(), null, $this->locale), 'attr' => array('class' => 'form-control'), ))
            ->add('i18n_description', TextareaI18nType::class,
                  array(
                      'required' => false,
                      'label' => $this->translator->trans('Description', array(), null, $this->locale), 'attr' => array('class' => 'form-control', 'style' => 'resize:vertical;'), ))
            ->add('place', TextType::class,
                  array(
                      'required' => false,
                      'label' => $this->translator->trans('Location', array(), null, $this->locale), 'attr' => array('class' => 'form-control'), ))
            ->add('live', null,
                  array(
                      'choice_label' => 'info',
                      'query_builder' => function ($repo) {
                          return $repo->createAbcSortQueryBuilder($this->locale);
                      },
                      'label' => $this->translator->trans('Channels', array(), null, $this->locale),
                      'attr' => array('class' => 'form-control'), ))
            ->add('display', CheckboxType::class,
                  array(
                      'required' => false,
                      'label' => $this->translator->trans('Announce', array(), null, $this->locale), ))
            ->add('create_serial', HiddenType::class,
                  array(
                      'required' => false,
                      'label' => $this->translator->trans('Create series', array(), null, $this->locale), ))
            ->add('duration', IntegerType::class,
                  array(
                      'required' => false,
                      'label' => $this->translator->trans('Duration', array(), null, $this->locale), 'attr' => array('class' => 'form-control'), ))
            ->add('i18n_already_held_message', TextI18nType::class,
                  array(
                      'required' => false,
                      'label' => $this->translator->trans('Already held event message', array(), null, $this->locale), 'attr' => array('class' => 'form-control', 'style' => 'resize:vertical;'), ))
            ->add('i18n_not_yet_held_message', TextI18nType::class,
                  array(
                      'required' => false,
                      'label' => $this->translator->trans('Not yet held event message', array(), null, $this->locale), 'attr' => array('class' => 'form-control', 'style' => 'resize:vertical;'), ))
            ->add('enable_chat', CheckboxType::class,
                  array(
                      'required' => false,
                      'attr' => array('aria-label' => $this->translator->trans('Enable Chat', array(), null, $this->locale)),
                      'label' => $this->translator->trans('Enable Chat', array(), null, $this->locale), ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Pumukit\SchemaBundle\Document\EmbeddedEvent',
        ));

        $resolver->setRequired('translator');
        $resolver->setRequired('locale');
    }

    public function getBlockPrefix()
    {
        return 'pumukitnewadmin_live_event';
    }
}
