<?php

/**
 * Description of OrganizationTransformer
 *
 */

namespace App\Form\DataTransformer;

use App\Entity\Masters\Ministry;
use App\Entity\Portal\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class OrganizationTransformer implements DataTransformerInterface {

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * Transforms an object (issue) to a string (number).
     *
     * @param  Issue|null $course
     * @return string
     */
    public function transform($Orgnization) {
        if (null === $Orgnization) {
            return '';
        }
        // return $Pss->getProgramStreamSchemeCode();
        
        $Orgnization = $Orgnization->getOrganizationName();
        return $Orgnization;
    }

    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $programStreamSchemeCode
     * @return Issue|null
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($Orgnization) {
        $Orgnization = $this->entityManager
                ->getRepository(Organization::class)
                ->findOneBy(array('organizationName' => $Orgnization));
        if (!$Orgnization) {
            return;
        }            
        if (null === $Orgnization) {
            throw new TransformationFailedException(sprintf(
                    'Invalid Organization ', $Orgnization
            ));
        }
        return $Orgnization;
    }

}
