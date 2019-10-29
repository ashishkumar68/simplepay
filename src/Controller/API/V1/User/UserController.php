<?php

namespace App\Controller\API\V1\User;

use App\Message\DeleteUserAccountCommand;
use App\Message\GetUserDetailsCommand;
use App\Message\RegisterUserCommand;
use App\Message\UpdateUserDetailsCommand;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Class UserController
 * @package App\Controller\API\V1\User
 */
class UserController extends FOSRestController
{
    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * UserController constructor.
     * @param MessageBusInterface $messageBus
     */
    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * Function to handle User Create API request
     * @Rest\Post("/")
     * @param Request $request
     * @return View
     */
    public function RegisterUser(Request $request)
    {
        $requestContent = $request->request->All();
        $name = $requestContent['name']??'';
        $email = $requestContent['email']??'';
        $mobile = $requestContent['mobile']??'';
        $password = $requestContent['password']??'';

        $envelope = $this->messageBus->dispatch(new RegisterUserCommand($name,$email,$mobile,$password));
        $handled = $envelope->last(HandledStamp::class);
        $response = $handled->getResult();

        return View::create($response, Response::HTTP_CREATED);

    }
    /**
     * Function to GET the details of user by using user id.
     * @Rest\Get("/{id}")
     * @param Request $request
     * @param $id
     * @return View
     */
    public function getUserDetails(Request $request, $id)
    {
        $envelope = $this->messageBus->dispatch(new GetUserDetailsCommand($id));
        $handled = $envelope->last(HandledStamp::class);
        $response = $handled->getResult();
        return View::create($response, Response::HTTP_OK);
    }

    /**
     * Function to handle User Delete API request
     * @Rest\Delete("/{id}")
     * @param Request $request
     * @param $id
     * @return View
     */
    public function deleteUser(Request $request, $id)
    {
        $envelope = $this->messageBus->dispatch(new DeleteUserAccountCommand($id));
        $handled = $envelope->last(HandledStamp::class);
        $response = $handled->getResult();

        return View::create($response, Response::HTTP_NO_CONTENT);
    }

    /**
     * Function to handle User Update API request
     * @Rest\Patch("/{id}")
     * @param Request $request
     * @param $id
     * @return View
     */
    public function UpdateUserDetails(Request $request, $id)
    {
        $name = $requestContent['name']??'';

        $envelope = $this->messageBus->dispatch(new UpdateUserDetailsCommand($id,$name));
        $handled = $envelope->last(HandledStamp::class);
        $response = $handled->getResult();

        return View::create($response, Response::HTTP_NO_CONTENT);
    }
}