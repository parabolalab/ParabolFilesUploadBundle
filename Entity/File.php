<?php

namespace Parabol\FilesUploadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use A2lix\TranslationFormBundle\Util\Knp\KnpTranslatable;
use Parabol\BaseBundle\Entity\Base\BaseEntity;
use Doctrine\ORM\Event\LifecycleEventArgs;
/**
 * Page
 *
 * @ORM\Table(name="parabol_file")
 * @ORM\Entity(repositoryClass="Parabol\FilesUploadBundle\Repository\FileRepository")
 */
class File extends BaseEntity
{

    use 
        ORMBehaviors\Sortable\Sortable,
        ORMBehaviors\Sluggable\Sluggable
        // ORMBehaviors\Translatable\Translatable
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
     * @ORM\Column(name="path", type="string", length=500)
     */
    private $path;

    /**
     * @ORM\Column(name="mime_type", type="string", length=100)
     */
    private $mimeType;

    /**
     * @ORM\Column(name="class", type="string", length=100, nullable=true)
     */
    private $class;

    /**
     * @ORM\Column(name="ref", type="string", length=65, nullable=true)
     */
    private $ref;

    /**
     * @ORM\Column(name="initRef", type="string", length=65, nullable=true)
     */
    private $initRef;

    /**
     * @ORM\Column(name="context", type="string", length=50, nullable=true)
     */
    private $context;

    /**
     * @ORM\Column(name="ref_admin_url", type="string", length=255, nullable=true)
     */
    private $refAdminUrl;

    /**
     * @ORM\Column(name="is_new", type="boolean")
     */
    private $isNew = true;

     /**
     * @ORM\Column(name="alt", type="string", length=255, nullable=true)
     */
    private $alt;

     /**
     * @ORM\Column(name="headline", type="string", length=255, nullable=true)
     */
    private $headline;

    /**
     * @ORM\Column(name="subheadline", type="string", length=255, nullable=true)
     */
    private $subheadline;

    /**
     * @ORM\Column(name="url", type="string", length=500, nullable=true)
     */
    private $url;

    /**
     * @ORM\Column(name="color", type="string", length=7, nullable=true)
     */
    private $color = '#ffffff';  

    /**
     * @ORM\Column(name="cssClass", type="string", length=500, nullable=true)
     */
    private $cssClass;

    /**
     * @ORM\Column(name="cssStyle", type="string", length=500, nullable=true)
     */
    private $cssStyle;  

    /**
     * @ORM\Column(name="width", type="smallint", nullable=true)
     */
    private $width;

     /**
      * @ORM\Column(name="height", type="smallint", nullable=true)
      */
    private $height;

    /**
      * @ORM\Column(name="size", type="integer", nullable=true)
      */
    private $size;


     /**
      * @ORM\Column(name="cropBoxData", type="json_array", nullable=true)
      */
    private $cropBoxData;


     /**
     * @ORM\Column(name="toRemove", type="string", length=65, nullable=true)
     */
    private $toRemove;


    public function __construct() {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        return $this;
    }

    public function getSluggableFields()
    {
        return $this->headline ? [ 'headline' ] : [ 'path' ];
    }
    
    public function __toString()
    {
        return $this->getFilename();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }    

    /**
     * Set path
     *
     * @param string $path
     * @return File
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    public function getCroppedPath()
    {
        return $this->getCropBoxData() ? preg_replace('/(\.[\w\d]{3})/', '-cropped$1', $this->path) : $this->path;
    }

    /**
     * Set mimeType
     *
     * @param string $mimeType
     * @return File
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * Get mimeType
     *
     * @return string 
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }


    /**
     * Set class
     *
     * @param string $class
     * @return File
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get class
     *
     * @return string 
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set ref
     *
     * @param integer $ref
     * @return File
     */
    public function setRef($ref)
    {
        $this->ref = $ref;

        return $this;
    }

    public function hasAssociation()
    {
        return $this->ref[0] !== '_';        
    }

    /**
     * Get ref
     *
     * @return integer 
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * Set initRef
     *
     * @param integer $initRef
     * @return File
     */
    public function setInitRef($initRef)
    {
        $this->initRef = $initRef;

        return $this;
    }

    /**
     * Get initRef
     *
     * @return integer 
     */
    public function getInitRef()
    {
        return $this->initRef;
    }

     /**
     * Set context
     *
     * @param string $context
     *
     * @return File
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set refAdminUrl
     *
     * @param string $refAdminUrl
     * @return File
     */
    public function setRefAdminUrl($refAdminUrl)
    {
        $this->refAdminUrl = $refAdminUrl;

        return $this;
    }

    /**
     * Get refAdminUrl
     *
     * @return string 
     */
    public function getRefAdminUrl()
    {
        return $this->refAdminUrl;
    }


    /**
     * Set isNew
     *
     * @param boolean $isNew
     * @return File
     */
    public function setIsNew($isNew)
    {
        $this->isNew = $isNew;

        return $this;
    }

    /**
     * Get isNew
     *
     * @return boolean 
     */
    public function getIsNew()
    {
        return $this->isNew;
    }

    /**
     * Get isNew
     *
     * @return boolean 
     */
    public function isNew()
    {
        return $this->isNew;
    }



    /**
     * Set hash
     *
     * @param string $hash
     *
     * @return File
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
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
     * Set color
     *
     * @param string $color
     *
     * @return File
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    public function isImage()
    {
        return $this->getMimeType() != 'image/svg+xml' && strpos($this->getMimeType(), 'image/') !== false;
    }


    public function isVideo()
    {
        return strpos($this->getMimeType(), 'video/') !== false;
    }

    public function getFileType()
    {
        if($this->isImage()) return 'image';
        elseif($this->isVideo()) return 'video';
        else
        {
            switch ($this->getMimeType()) {
                case 'application/pdf':
                    return 'document';
                    break;
                
                case 'application/zip':
                    return 'archive';
                    break;

                default:
                    return null;
                    break;
            }
        }
    }

    public function getFilename()
    {
        return basename($this->getPath());
    }

    public function getPathForThumb()
    {
        if($this->isImage())
        {
            return $this->getPath();      
        }
        else
        {
            switch($this->getMimeType())
            {
                case 'application/pdf':
                    return '/bundles/parabolfilesupload/images/filesIcons/pdf_icon.png';
                break;
                case 'application/zip':
                    return '/bundles/parabolfilesupload/images/filesIcons/zip_icon.png';
                break;
                case 'video/mp4':
                    return '/bundles/parabolfilesupload/images/filesIcons/mp4_icon.png';
                break;
                case 'image/svg+xml':
                    return $this->getPath();
                default:
                    return '/bundles/parabolfilesupload/images/filesIcons/default_icon.png';

            }
        }
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


    /**
     * Set cssClass
     *
     * @param string $cssClass
     * @return File
     */
    public function setCssClass($cssClass)
    {
        $this->cssClass = $cssClass;

        return $this;
    }

    /**
     * Get cssClass
     *
     * @return string 
     */
    public function getCssClass()
    {
        return $this->cssClass;
    }

    /**
     * Set cssStyle
     *
     * @param string $cssStyle
     * @return File
     */
    public function setCssStyle($cssStyle)
    {
        $this->cssStyle = $cssStyle;

        return $this;
    }

    /**
     * Get cssStyle
     *
     * @return string 
     */
    public function getCssStyle()
    {
        return $this->cssStyle;
    }

    public function getSlug()
    {
        return $this->slug ? $this->slug : \Parabol\BaseBundle\Util\PathUtil::slugize(pathinfo($this->path)['filename']);
    }

    /**
     * Set width
     *
     * @param integer $width
     *
     * @return File
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     *
     * @return File
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set size
     *
     * @param integer $size
     *
     * @return File
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }
    /**
     * Set cropBoxData
     *
     * @param integer $cropBoxData
     *
     * @return File
     */
    public function setCropBoxData($cropBoxData)
    {
        $this->cropBoxData = $cropBoxData;

        return $this;
    }

    /**
     * Get cropBoxData
     *
     * @return integer
     */
    public function getCropBoxData()
    {
        return $this->cropBoxData;
    }

    /**
     * Set toRemove
     *
     * @param integer $toRemove
     * @return File
     */
    public function setToRemove($toRemove)
    {
        $this->toRemove = $toRemove;

        return $this;
    }

    /**
     * Get toRemove
     *
     * @return integer 
     */
    public function getToRemove()
    {
        return $this->toRemove;
    }



}
