<?php

namespace Pumukit\DirectBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pumukit\DirectBundle\Document\Direct
 *
 * @MongoDB\Document(repositoryClass="Pumukit\DirectBundle\Repository\DirectRepository")
 */
class Direct
{
  const DIRECT_TYPE_FMS = 0;
  const DIRECT_TYPE_WMS = 1;

  /** 
   * @var int $id
   * 
   * @MongoDB\Id
   */
  private $id;

  /**
   * @var string $url
   * 
   * @MongoDB\String
   */
  private $url;

  /**
   * @var string $passwd
   *
   * @MongoDB\String
   */
  private $passwd;
  
  /**
   * @var int $direct_type_id
   *
   * @MongoDB\Int
   */
  private $direct_type_id;
  
  /**
   * @var int $resolution_width
   *
   * @MongoDB\Int
   */
  private $resolution_width = 0;

  /**
   * @var int $resolution_height
   *
   * @MongoDB\Int
   */
  private $resolution_height = 0;

  /**
   * @var string $qualities
   *
   * @MongoDB\String
   */
  private $qualities;

  /**
   * @var string $ip_source
   *
   * @MongoDB\String
   */
  private $ip_source;

  /**
   * @var string $source_name
   *
   * @MongoDB\String
   */
  private $source_name;
   
  /**
   * @var boolean $index_play
   * 
   * @MongoDB\Boolean
   */
  private $index_play = false;

  /**
   * @var boolean $broadcasting
   *
   * @MongoDB\Boolean
   */
  private $broadcasting = false;

  /**
   * @var boolean $debug
   *
   * @MongoDB\Boolean
   */
  private $debug = false;

  /**
   * @var string $name
   * 
   * @MongoDB\Raw
   */
  private $name = array('en'=>'');

  /**
   * @var string $description
   *
   * @MongoDB\Raw
   */
  private $description = array('en'=>'');
  
  /**
   * @var locale $locale
   */
  private $locale = 'en';

  /**
   * Get id
   *
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Set url
   *
   * @param string $url
   */
  public function setUrl($url)
  {
    $this->url = $url;
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
   * Set passwd
   *
   * @param string $passwd
   */
  public function setPasswd($passwd)
  {
    $this->passwd = $passwd;
  }
  
  /**
   * Get passwd
   *
   * @return string
   */
  public function getPasswd()
  {
    return $this->passwd;
  }

  /**
   * Set direct_type_id
   *
   * @param int $direct_type_id
   */
  public function setDirectTypeId($direct_type_id)
  {
    $this->direct_type_id = $direct_type_id;
  }
  
  /**
   * Get direct_type_id
   *
   * @return int
   */
  public function getDirectTypeId()
  {
    return $this->direct_type_id;
  }

  /**
   * Set resolution_width
   *
   * @param int $resolution_width
   */
  public function setResolutionWidth($resolution_width)
  {
    $this->resolution_width = $resolution_width;
  }
  
  /**
   * Get resolution_width
   *
   * @return int
   */
  public function getResolutionWidth()
  {
    return $this->resolution_width;
  }

  /**
   * Set resolution_height
   *
   * @param int $resolution_height
   */
  public function setResolutionHeight($resolution_height)
  {
    $this->resolution_height = $resolution_height;
  }
  
  /**
   * Get resolution_height
   *
   * @return int
   */
  public function getResolutionHeight()
  {
    return $this->resolution_height;
  } 

  /**
   * Set qualities
   *
   * @param string $qualities
   */
  public function setQualities($qualities)
  {
    $this->qualities = $qualities;
  }
  
  /**
   * Get qualities
   *
   * @return string
   */
  public function getQualities()
  {
    return $this->qualities;
  }  

  /**
   * Set ip_source
   *
   * @param string $ip_source
   */
  public function setIpSource($ip_source)
  {
    $this->ip_source = $ip_source;
  }
  
  /**
   * Get ip_source
   *
   * @return string
   */
  public function getIpSource()
  {
    return $this->ip_source;
  }

  /**
   * Set source_name
   *
   * @param string $source_name
   */
  public function setSourceName($source_name)
  {
    $this->source_name = $source_name;
  }
  
  /**
   * Get source_name
   *
   * @return string
   */
  public function getSourceName()
  {
    return $this->source_name;
  }

  /**
   * Set index_play
   *
   * @param boolean $index_play
   */
  public function setIndexPlay($index_play)
  {
    $this->index_play = $index_play;
  }
  
  /**
   * Get index_play
   *
   * @return boolean
   */
  public function getIndexPlay()
  {
    return $this->index_play;
  }

  /**
   * Set broadcasting
   *
   * @param boolean $broadcasting
   */
  public function setBroadcasting($broadcasting)
  {
    $this->broadcasting = $broadcasting;
  }
  
  /**
   * Get broadcasting
   *
   * @return boolean
   */
  public function getBroadcasting()
  {
    return $this->broadcasting;
  }

  /**
   * Set debug
   *
   * @param boolean $debug
   */
  public function setDebug($debug)
  {
    $this->debug = $debug;
  }
  
  /**
   * Get debug
   *
   * @return boolean
   */
  public function getDebug()
  {
    return $this->debug;
  } 

  /**
   * Set name
   *
   * @param string $name
   */
  public function setName($name, $locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    $this->name[$locale] = $name;
  }
  
  /**
   * Get name
   *
   * @return string
   */
  public function getName($locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    if (!isset($this->name[$locale])){
      return null;
    }
    return $this->name[$locale];
  }

  /**
   * Set description
   *
   * @param string $description
   */
  public function setDescription($description, $locale = null)
  {
    if ($locale == null) {
      $locale = $this->locale;
    }
    $this->description[$locale] = $description;
  }
  
  /**
   * Get description
   *
   * @return string
   */
  public function getDescription($locale = null)
  {  
    if ($locale == null) {
      $locale = $this->locale;
    }
    if (!isset($this->description[$locale])){
      return null;
    }
    return $this->description[$locale];
  }

  /**
   * Set locale
   *
   * @param string $locale
   */
  public function setLocale($locale)
  {
    $this->locale = $locale;
  }
  
  /**
   * Get locale
   *
   * @return string
   */
  public function getLocale()
  {
    return $this->locale;
  }

}