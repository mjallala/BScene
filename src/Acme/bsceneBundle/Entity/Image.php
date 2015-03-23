<?php

/* 
 * Image.php
 * The entity for the Image object
 * Revision History:
 *      16.03.2015: created, Mahmoud Jallala
 */
//src/bsceneBundle/Entity/Image.php

namespace Acme\bsceneBundle\Entity;



use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="Image")
 */
class Image
{
     /**
     * @ORM\Column(type="integer", length=5,  unique=true)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $URL;
    
    
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $Name;
    
     /**
     * @ORM\OneToOne(targetEntity="Meeting", mappedBy="image")
     */
    protected $event;
    
    /**
     * @ORM\OneToOne(targetEntity="Categories", mappedBy="image")
     */
    protected $category;
    
    

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
     * Set URL
     *
     * @param string $uRL
     * @return Image
     */
    public function setURL($uRL)
    {
        $this->URL = $uRL;
    
        return $this;
    }

    /**
     * Get URL
     *
     * @return string 
     */
    public function getURL()
    {
        return $this->URL;
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return Image
     */
    public function setName($name)
    {
        $this->Name = $name;
    
        return $this;
    }

    /**
     * Get Name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->Name;
    }



    /**
     * Set event
     *
     * @param \Acme\bsceneBundle\Entity\Meeting $event
     * @return Image
     */
    public function setEvent(\Acme\bsceneBundle\Entity\Meeting $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return \Acme\bsceneBundle\Entity\Meeting 
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set category
     *
     * @param \Acme\bsceneBundle\Entity\Categories $category
     * @return Image
     */
    public function setCategory(\Acme\bsceneBundle\Entity\Categories $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Acme\bsceneBundle\Entity\Categories 
     */
    public function getCategory()
    {
        return $this->category;
    }
}
