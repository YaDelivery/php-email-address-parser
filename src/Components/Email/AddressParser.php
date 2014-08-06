<?php
/**
 * @author Ura Kozyrev <yk@multiship.ru>
 */

namespace Kozz\Components\Email;

use RegexIterator;
use ArrayIterator;

class AddressParser
{

  /**
   * @var string
   */
  protected $domain;

  /**
   * @var array
   */
  protected $raw;

  /**
   * @var array
   */
  protected $container = [];


  public static function parse($emails, $domain = 'domain.default')
  {
    return new self($emails, $domain);
  }

  public function __construct($emails, $domain = 'domain.default')
  {
    if (is_string($emails))
    {
      $emails = $this->processInitString($emails);
    }
    $this->setDomain($domain);
    $this->raw = $emails;
  }

  public function setDomain($domain)
  {
    if (!is_string($domain))
    {
      throw new \UnexpectedValueException("Domain should be string : ", var_export($domain, true));
    }
    if (!preg_match('/^([a-zA-Z0-9_-])([a-zA-Z0-9\._-]+)\.([a-zA-Z0-9\._-]+)/iu', $domain))
    {
      throw new \UnexpectedValueException("Invalid domain name: $domain");
    }
    $this->domain = $domain;

    return $this;
  }

  public function toArray()
  {
    return array_unique(iterator_to_array($this->getProcessIterator()));
  }

  protected function getProcessIterator()
  {
    $raw = new ArrayIterator($this->raw);

    $autoComplete = new RegexIterator(clone $raw, '/^([a-z0-9\-._]+)(\@)$/iu', RegexIterator::REPLACE);
    $autoComplete->replacement = '$1$2'.$this->domain;

    $iterator = new \AppendIterator;
    $iterator->append($raw);
    $iterator->append($autoComplete);

    $filter = new \CallbackFilterIterator($iterator, function($value, $key, $iterator){
      return false !== filter_var($value, FILTER_VALIDATE_EMAIL);
    });
    return $filter;
  }

  /**
   * @param $emails
   *
   * @return array
   * @throws \UnexpectedValueException
   */
  protected function processInitString($emails)
  {
    if (!is_string($emails))
    {
      throw new \UnexpectedValueException("Emails are not string: ", var_export($emails, true));
    }
    $emails = preg_split("/[\s,]+/", $emails);

    return $emails;
  }

}