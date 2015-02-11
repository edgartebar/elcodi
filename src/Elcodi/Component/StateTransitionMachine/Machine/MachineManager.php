<?php

/*
 * This file is part of the Elcodi package.
 *
 * Copyright (c) 2014 Elcodi.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 * @author Aldo Chiecchia <zimage@tiscali.it>
 * @author Elcodi Team <tech@elcodi.com>
 */

namespace Elcodi\Component\StateTransitionMachine\Machine;

use StdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Elcodi\Component\StateTransitionMachine\Definition\Transition;
use Elcodi\Component\StateTransitionMachine\ElcodiStateTransitionMachineEvents;
use Elcodi\Component\StateTransitionMachine\Entity\Interfaces\StateLineInterface;
use Elcodi\Component\StateTransitionMachine\Entity\StateLineStack;
use Elcodi\Component\StateTransitionMachine\Event\InitializationEvent;
use Elcodi\Component\StateTransitionMachine\Event\TransitionEvent;
use Elcodi\Component\StateTransitionMachine\Exception\ObjectAlreadyInitializedException;
use Elcodi\Component\StateTransitionMachine\Exception\ObjectNotInitializedException;
use Elcodi\Component\StateTransitionMachine\Exception\StateNotReachableException;
use Elcodi\Component\StateTransitionMachine\Exception\TransitionNotAccessibleException;
use Elcodi\Component\StateTransitionMachine\Exception\TransitionNotValidException;
use Elcodi\Component\StateTransitionMachine\Factory\StateLineFactory;
use Elcodi\Component\StateTransitionMachine\Machine\Interfaces\MachineInterface;

/**
 * Class MachineManager
 */
class MachineManager
{
    /**
     * @var MachineInterface
     *
     * Machine
     */
    protected $machine;

    /**
     * @var EventDispatcherInterface
     *
     * Event Dispatcher
     */
    protected $eventDispatcher;

    /**
     * @var StateLineFactory
     *
     * StateLine factory
     */
    protected $stateLineFactory;

    /**
     * Construct
     *
     * @param MachineInterface         $machine          Machine
     * @param EventDispatcherInterface $eventDispatcher  Event Dispatcher
     * @param StateLineFactory         $stateLineFactory StateLine Factory
     */
    public function __construct(
        MachineInterface $machine,
        EventDispatcherInterface $eventDispatcher,
        StateLineFactory $stateLineFactory
    )
    {
        $this->machine = $machine;
        $this->eventDispatcher = $eventDispatcher;
        $this->stateLineFactory = $stateLineFactory;
    }

    /**
     * Initialize the object into the machine
     *
     * @param StdClass       $object         $object Object
     * @param StateLineStack $stateLineStack StateLine Stack
     * @param string         $description    Description
     *
     * @return StateLineStack StateLine stack given
     *
     * @throws ObjectAlreadyInitializedException Object already initialized
     */
    public function initialize(
        $object,
        StateLineStack $stateLineStack,
        $description
    )
    {
        if ($stateLineStack->getLastStateLine() instanceof StateLineInterface) {

            throw new ObjectAlreadyInitializedException();
        }

        $pointOfEntry = $this
            ->machine
            ->getPointOfEntry();

        $stateLine = $this->createStateLine(
            $pointOfEntry,
            $description
        );

        $stateLineStack->addStateLine($stateLine);

        $this->dispatchInitializationEvents(
            $this->machine,
            $object,
            $stateLineStack
        );

        return $stateLineStack;
    }

    /**
     * Applies a transition given a object
     *
     * @param StdClass       $object         $object Object
     * @param StateLineStack $stateLineStack StateLine Stack
     * @param string         $transitionName Transition name
     * @param string         $description    Description
     *
     * @return StateLineStack StateLine stack given
     *
     * @throws TransitionNotAccessibleException Transition not accessible
     * @throws TransitionNotValidException      Invalid transition
     * @throws ObjectNotInitializedException    Object needs to be initialized in machine
     */
    public function transition(
        $object,
        StateLineStack $stateLineStack,
        $transitionName,
        $description
    )
    {
        $startState = $stateLineStack->getLastStateLine();

        if (!($startState instanceof StateLineInterface)) {

            throw new ObjectNotInitializedException();
        }

        $transition = $this
            ->machine
            ->transition(
                $startState->getName(),
                $transitionName
            );
        $stateLine = $this->createStateLine(
            $transition->getFinal()->getName(),
            $description
        );

        $stateLineStack->addStateLine($stateLine);

        $this->dispatchTransitionEvents(
            $this->machine,
            $object,
            $stateLineStack,
            $transition
        );

        return $stateLineStack;
    }

    /**
     * Applies a transition given a object
     *
     * @param StdClass       $object         $object Object
     * @param StateLineStack $stateLineStack StateLine Stack
     * @param string         $transitionName Transition name
     * @param string         $description    Description
     *
     * @return StateLineStack StateLine stack given
     *
     * @throws StateNotReachableException    State is not reachable
     * @throws ObjectNotInitializedException Object needs to be initialized in machine
     */
    public function reachState(
        $object,
        StateLineStack $stateLineStack,
        $transitionName,
        $description
    )
    {
        $startState = $stateLineStack->getLastStateLine();

        if (!($startState instanceof StateLineInterface)) {

            throw new ObjectNotInitializedException();
        }

        $transition = $this
            ->machine
            ->reachState(
                $startState->getName(),
                $transitionName
            );

        $stateLine = $this->createStateLine(
            $transition->getFinal()->getName(),
            $description
        );

        $stateLineStack->addStateLine($stateLine);

        $this->dispatchTransitionEvents(
            $this->machine,
            $object,
            $stateLineStack,
            $transition
        );

        return $stateLineStack;
    }

    /**
     * Create an state line given its name and description
     *
     * @param string $name        Name
     * @param string $description Description
     *
     * @return StateLineInterface State Line
     */
    public function createStateLine($name, $description)
    {
        $stateLine = $this
            ->stateLineFactory
            ->create()
            ->setName($name)
            ->setDescription($description);

        return $stateLine;
    }

    /**
     * Throw initialization events
     *
     * @param MachineInterface $machine        Machine
     * @param StdClass         $object         $object Object
     * @param StateLineStack   $stateLineStack StateLine Stack
     *
     * @return $this Self object
     */
    protected function dispatchInitializationEvents(
        MachineInterface $machine,
        $object,
        StateLineStack $stateLineStack
    )
    {
        $this
            ->eventDispatcher
            ->dispatch(
                str_replace(
                    '{machine_id}',
                    $machine->getId(),
                    ElcodiStateTransitionMachineEvents::INITIALIZATION
                ),
                new InitializationEvent(
                    $object,
                    $stateLineStack
                )
            );
    }

    /**
     * Throw transition events
     *
     * @param MachineInterface $machine        Machine
     * @param StdClass         $object         $object Object
     * @param StateLineStack   $stateLineStack StateLine Stack
     * @param Transition       $transition     Transition
     *
     * @return $this Self object
     */
    protected function dispatchTransitionEvents(
        MachineInterface $machine,
        $object,
        StateLineStack $stateLineStack,
        Transition $transition
    )
    {
        $this
            ->eventDispatcher
            ->dispatch(
                ElcodiStateTransitionMachineEvents::ALL_TRANSITIONS,
                new TransitionEvent(
                    $object,
                    $stateLineStack,
                    $transition
                )
            );

        $this
            ->eventDispatcher
            ->dispatch(
                str_replace(
                    array(
                        '{machine_id}',
                        '{state_name}',
                    ),
                    array(
                        $machine->getId(),
                        $transition->getStart()->getName(),
                    ),
                    ElcodiStateTransitionMachineEvents::TRANSITION_FROM_STATE
                ),
                new TransitionEvent(
                    $object,
                    $stateLineStack,
                    $transition
                )
            );

        $this
            ->eventDispatcher
            ->dispatch(
                str_replace(
                    array(
                        '{machine_id}',
                        '{state_name}',
                    ),
                    array(
                        $machine->getId(),
                        $transition->getFinal()->getName(),
                    ),
                    ElcodiStateTransitionMachineEvents::TRANSITION_TO_STATE
                ),
                new TransitionEvent(
                    $object,
                    $stateLineStack,
                    $transition
                )
            );

        $this
            ->eventDispatcher
            ->dispatch(
                str_replace(
                    array(
                        '{machine_id}',
                        '{transition_name}',
                    ),
                    array(
                        $machine->getId(),
                        $transition->getName(),
                    ),
                    ElcodiStateTransitionMachineEvents::TRANSITION
                ),
                new TransitionEvent(
                    $object,
                    $stateLineStack,
                    $transition
                )
            );
    }
}
