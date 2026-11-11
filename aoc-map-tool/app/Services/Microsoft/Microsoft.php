<?php

namespace App\Services\Microsoft;

use App\Repositories\Events\EventsWriteRepository;
use Auth;
use Exception;
use Microsoft\Graph\Generated\Users\Item\UserItemRequestBuilder;
use Microsoft\Graph\Generated\Users\UsersRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Graph\Generated\Models\User;

abstract class Microsoft
{
    private $tokenRequestContext;
    private $graphServiceClient;

    public function __construct(
        private ?User $currentUser,
        private EventsWriteRepository $eventsWriteRepository
    ) {
        $this->currentUser = null;
        $this->tokenRequestContext = new ClientCredentialContext(
            env('_OAUTH_TENANTID'),
            env('_OAUTH_CLIENTID'),
            env('_OAUTH_SECRET')
        );
        $this->graphServiceClient = new GraphServiceClient($this->tokenRequestContext);
    }

    /**
     * @throws Exception
     */
    public function setUserByEmail(string $email): self
    {
        $requestConfiguration = new UsersRequestBuilderGetRequestConfiguration();
        $queryParameters = UsersRequestBuilderGetRequestConfiguration::createQueryParameters();
        $queryParameters->filter = sprintf("mail eq '%s'", $email);
        $requestConfiguration->queryParameters = $queryParameters;
        $users = $this->graphServiceClient->users()->get($requestConfiguration)->wait();

        if ($users->getValue()) {
            $this->currentUser = $users->getValue()[0];

            return $this;
        }

        throw new Exception(sprintf('There is no %s user found.', $email));
    }

    public function getUser(): User
    {
        return $this->currentUser;
    }

    public function getUserRequestBuilder(): UserItemRequestBuilder
    {
        return $this->graphServiceClient->users()->byUserId($this->getUser()->getId());
    }

    protected function hasUser(): bool
    {
        return !is_null($this->currentUser);
    }

    /**
     * It's mandatory to always throw an exception if we do not have current user.
     *
     * @throws Exception
     */
    protected function userNotSetException(): Exception
    {
        throw new Exception('Current user property can\'t be null');
    }

    protected function createAOCEvent(array $data): int
    {
        $data['created_by'] = Auth::user()->id;

        return $this->eventsWriteRepository->create($data);
    }

    protected function updateAOCEvent(string $id, array $data): int
    {
        $this->eventsWriteRepository->updateByEventId($id, $data);

        return $id;
    }
}
