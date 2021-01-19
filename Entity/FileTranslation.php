<?php

namespace Parabol\FilesUploadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TranslationInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslationTrait;
use A2lix\TranslationFormBundle\Util\Knp\KnpTranslatable;
use Parabol\BaseBundle\Entity\Base\BaseEntity;
use Doctrine\ORM\Event\LifecycleEventArgs;
/**
 * FileTranslation
 *
 * @ORM\Table(name="parabol_file_translation")
 * @ORM\Entity()
 */
class FileTranslation implements TranslationInterface
{

    use 
      TranslationTrait
    ;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;  

     /**
     * @ORM\Column(name="alt", type="string", length=255, nullable=true)
     */
    private $alt;

     /**
     * @ORM\Column(name="headline", type="string", length=255, nullable=true)
     */
    private $headline;

    /**
     * @ORM\Column(name="subheadline", type="text", nullable=true)
     */
    private $subheadline;

    /**
     * @ORM\Column(name="url", type="string", length=500, nullable=true)
     */
    private $url;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }  

    
    public function __toString()
    {
        return $this->getAlt();
    }

   
    /**
     * Set alt
     *
     * @param string $alt
     *
     * @return File
     */
    public function setAlt($alt)
    {
        $this->alt = $alt;

        return $this;
    }

    /**
     * Get alt
     *
     * @return string
     */
    public function getAlt()
    {
        return $this->alt;
    }

    /**
     * Set headline
     *
     * @param string $headline
     *
     * @return File
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;

        return $this;
    }

    /**
     * Get headline
     *
     * @return string
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * Set subheadline
     *
     * @param string $subheadline
     *
     * @return File
     */
    public function setSubheadline($subheadline)
    {
        $this->subheadline = $subheadline;

        return $this;
    }

    /**
     * Get subheadline
     *
     * @return string
     */
    public function getSubheadline()
    {
        return $this->subheadline;
    }

    /**
     * Set url
     *
     * @param string $url
     *
     * @return File
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }



}
