<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Olivier Chauvel <olivier@generation-multiple.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Genemu\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\ArrayChoiceList;
use Doctrine\Common\Persistence\ManagerRegistry;

use Genemu\Bundle\FormBundle\Form\DataTransformer\ChoiceToJsonTransformer;
use Genemu\Bundle\FormBundle\Form\DataTransformer\EntityIdToJsonTransformer;

use Genemu\Bundle\FormBundle\Form\ChoiceList\AjaxChoiceList;

/**
 * JQueryAutocompleterType
 *
 * @author Olivier Chauvel <olivier@generation-multiple.com>
 */
class JQueryAutocompleterType extends AbstractType
{
    protected $registry;

    /**
     * Construct
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        if (!$options['route_name']) {
            $choiceList = null;

            if (isset($options['choices']) && $options['choices']) {
                $choiceList = $options['choices'];
            }

            if (isset($options['choice_list']) && $options['choice_list']) {
                $choiceList = $options['choice_list']->getChoices();
            }

            if ($choiceList) {
                $choices = array();
                foreach ($choiceList as $value => $label) {
                    $choices[] = array(
                        'label' => $label,
                        'value' => $value
                    );
                }

                $builder->setAttribute('choice_list', new ArrayChoiceList($choices));
            }
        }

        if (isset($options['multiple']) && $options['multiple']) {
            $transformer = null;

            switch ($options['widget']) {
                case 'entity':
                    $transformer = new EntityIdToJsonTransformer($options['choice_list']);
                    break;
                case 'choice':
                    $transformer = new ChoiceToJsonTransformer();
                default:
                    break;
            }

            if ($transformer) {
                $builder->appendClientTransformer($transformer);
            }
        }

        $builder->setAttribute('route_name', $options['route_name']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form)
    {
        $data = $form->getClientData();
        $data = is_array($data) ? json_decode($data, true) : $data;
        $value = '';

        if ($form->hasAttribute('multiple') && $form->getAttribute('multiple') && $data) {
            foreach ($data as $val) {
                $value .= $val['label'].', ';
            }
        } else {
            if ($form->hasAttribute('choice_list') && $form->getAttribute('choice_list')) {
                $choices = $form->getAttribute('choice_list')->getChoices();

                foreach ($choices as $val => $label) {
                    if ($val == $data) {
                        $value = $label;
                    }
                }
            } else {
                $value = $data;
            }
        }

        if (!$value) {
            $view->set('value', '');
        }

        $view
            ->set('autocompleter_value', $value)
            ->set('route_name', $form->getAttribute('route_name'));
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'widget' => 'choice',
            'route_name' => null
        );

        if (isset($options['widget']) && 'entity' === $options['widget']) {
            $defaultOptions = array_replace($defaultOptions, array(
                'em' => null,
                'class' => null,
                'property' => null,
                'query_builder' => null,
                'choices' => array(),
                'group_by' => null
            ));
        }

        $options = array_replace($defaultOptions, $options);

        if ('entity' === $options['widget'] && $options['route_name']) {
            $options['choice_list'] = new AjaxChoiceList(
                $this->registry->getManager($options['em']),
                $options['class'],
                $options['property'],
                $options['query_builder'],
                $options['choices'],
                $options['group_by']
            );
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(array $options)
    {
        return $options['widget'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'genemu_jqueryautocompleter';
    }
}
