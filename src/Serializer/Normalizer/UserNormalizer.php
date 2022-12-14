<?php

namespace App\Serializer\Normalizer;

use App\Entity\User;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UserNormalizer implements NormalizerInterface, NormalizerAwareInterface, CacheableSupportsMethodInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'USER_NORMALIZER_ALREADY_CALLED';

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /** {@inheritdoc} */
    public function normalize($object, string $format = null, array $context = []): array
    {
        $isOwner = $this->userIsOwner($object);
        if ($isOwner) {
            $context['groups'][] = 'owner:read';
        }

        $context[self::ALREADY_CALLED][] = true;

        $data = $this->normalizer->normalize($object, $format, $context);

        $data['isMe'] = $isOwner;

        return $data;
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof User;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return false;
    }

    private function userIsOwner(User $user): bool
    {
        /** @var User|null $authenticatedUser */
        $authenticatedUser = $this->security->getUser();

        if (!$authenticatedUser) {
            return false;
        }

        return $authenticatedUser->getEmail() === $user->getEmail();
    }
}
