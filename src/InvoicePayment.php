<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Killbill\Client;

use Killbill\Client\Exception\Exception;
use Killbill\Client\Type\InvoicePaymentAttributes;

/**
 * 
 *
 * @author Julien Fastré <julien.fastre@champs-libres.coop>
 */
class InvoicePayment extends InvoicePaymentAttributes
{
    /**
     * @param bool          $externalPayment
     * @param string|null   $user    User requesting the creation
     * @param string|null   $reason  Reason for the creation
     * @param string|null   $comment Any addition comment
     * @param string[]|null $headers Any additional headers
     *
     * @return InvoicePayment|null The newly created account
     */
    public function create($externalPayment = false, array $pluginProperty = array(), $user = null, $reason = null, $comment = null, $headers = null)
    {
        $queryData = array();
        
        if ($externalPayment) {
            $queryData['externalPayment'] = 'true';
        }
        
        if (count($pluginProperty) > 0) {
            $queryData['pluginProperty'] = \implode('\n', $pluginProperty);
        }
        
        if ($this->getTargetInvoiceId() === null) {
            throw new \RuntimeException("The targetInvoiceId must be set");
        }
        
        if ($this->getAccountId() === null) {
            throw new \RuntimeException("The accountId must be set");
        }
        
        $query = $this->makeQuery($queryData);
        $response = $this->createRequest(
            Client::PATH_INVOICES.'/'.$this->getTargetInvoiceId().'/payments'.$query, 
            $user, 
            $reason, 
            $comment, 
            $headers
            );

        try {
            /** @var InvoicePayment|null $object */
            $object = $this->getFromResponse(InvoicePayment::class, $response, $headers);
        } catch (Exception $e) {
            $this->logger->error($e);

            return null;
        }

        return $object;
    }
}
