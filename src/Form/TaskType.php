<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Enum\TaskPriority;
use App\Domain\Enum\TaskStatus;
use App\Entity\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'constraints' => [
                    new NotBlank(message: 'Task title is required.'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'class' => TaskStatus::class,
                'label' => 'Status',
            ])
            ->add('priority', EnumType::class, [
                'class' => TaskPriority::class,
                'label' => 'Priority',
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Due Date',
                'widget' => 'single_text',
                'required' => false,
            ])
        ;

        $originalDueDate = $options['original_due_date'];

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($originalDueDate): void {
            $task = $event->getData();
            if (!$task instanceof Task) {
                return;
            }

            $dueDate = $task->getDueDate();
            if (null === $dueDate) {
                return;
            }

            if ($originalDueDate instanceof \DateTimeImmutable && $originalDueDate->format('Y-m-d') === $dueDate->format('Y-m-d')) {
                return;
            }

            $today = new \DateTimeImmutable('today');
            if ($dueDate < $today) {
                $event->getForm()->get('dueDate')->addError(
                    new FormError('Due date cannot be in the past.')
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'original_due_date' => null,
        ]);

        $resolver->setAllowedTypes('original_due_date', ['null', \DateTimeImmutable::class]);
    }
}
