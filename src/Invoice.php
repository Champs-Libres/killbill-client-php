<?php
/*
 * Copyright 2011-2017 The Billing Project, LLC
 *
 * Ning licenses this file to you under the Apache License, version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License.  You may obtain a copy of the License at:
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.  See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace Killbill\Client;

use Killbill\Client\Exception\Exception;
use Killbill\Client\Traits\CustomFieldTrait;
use Killbill\Client\Traits\TagTrait;
use Killbill\Client\Type\InvoiceAttributes;
use Killbill\Client\Type\InvoicePaymentAttributes;

/**
 * Invoice actions
 */
class Invoice extends InvoiceAttributes
{
    /**
     * Type to use for custom fields
     */
    const CUSTOMFIELD_OBJECTTYPE = CustomField::OBJECTTYPE_INVOICE;

    /**
     * @param bool|null     $withItems ?
     * @param string[]|null $headers   Any additional headers
     *
     * @return Invoice|null The fetched invoice
     */
    public function get($withItems, $headers = null)
    {
        $queryData = array();
        if ($withItems) {
            $queryData['withItems'] = 'true';
        }

        $query = $this->makeQuery($queryData);
        $response = $this->getRequest(Client::PATH_INVOICES.'/'.$this->getInvoiceId().$query, $headers);

        try {
            /** @var Invoice|null $object */
            $object = $this->getFromBody(Invoice::class, $response);
        } catch (Exception $e) {
            $this->logger->error($e);

            return null;
        }

        return $object;
    }
    
    public function create($accountId, $targetDate = null, $user = null, $reason = null, $comment = null, $headers = null)
    {
        $queryData = array();
        
        $queryData['accountId'] = $accountId;
        
        if ($targetDate !== NULL) {
            $queryData['targetDate'] = $targetDate;
        }
        
        $query = $this->makeQuery($queryData);
        $response = $this->createRequest(CLIENT::PATH_INVOICES.$query, $user, $reason, $comment, $headers);
        
        try {
            /** @var Invoice|null $object */
            $object = $this->getFromResponse(Invoice::class, $response, $headers);
        } catch (Exception $e) {
            $this->logger->error($e);

            return null;
        }

        return $object;
    }
    
    /**
     * Get payments for this invoice
     *
     * @param  boolean       $withPluginInfo
     * @param  boolean       $withAttempts
     * @param  string[]|null $headers        Any additional headers
     *
     * @return InvoicePayment[]
     */
    public function getPayments(
        $withPluginInfo = false,
        $withAttempts = false,
        $headers = null
    ) {
        $queryData = array();
        if ($withAttempts) {
            $queryData['withAttempts'] = 'true';
        }

        if ($withPluginInfo) {
            $queryData['withPluginInfos'] = 'true';
        }

        $query = $this->makeQuery($queryData);
        $response = $this->getRequest(
            Client::PATH_INVOICES.'/'.$this->getInvoiceId().Client::SUB_PATH_INVOICES_PAYMENT.$query,
            $headers
        );

        try {
            /** @var InvoicePayment[]|null $object */
            $object = $this->getFromBody(InvoicePaymentAttributes::class, $response);
        } catch (Exception $e) {
            $this->logger->error($e);

            return null;
        }

        return $object;
    }

    /**
     * @param string[]|null $headers Any additional headers
     *
     * @return string|null
     */
    public function getInvoiceAsHTML($headers = null)
    {
        $response = $this->getRequest(Client::PATH_INVOICES.'/'.$this->getInvoiceId().'/html', $headers);

        return $response->body;
    }
    
    public function charge(
        $requestedDate = null, 
        $payInvoice = false,
        array $pluginProperty = array(),
        $autoCommit = false,
        $paymentExternalKey = null,
        $transactionExternalKey = null,
        $user = null,
        $reason = null,
        $comment = null,
        $headers = null
    ) {
        $queryData = array();
        
        if ($requestedDate) {
            $queryData['requestedDate'] = $requestedDate;
        }
        
        if ($payInvoice) {
            $queryData['payInvoice'] = 'true';
        }
        
        if (count($pluginProperty) > 0) {
            $queryData['pluginProperty'] = \implode('\n', $pluginProperty);
        }
        
        if (is_bool((bool) $autoCommit)) {
            $queryData['autoCommit'] = (bool) $autoCommit ? 'true' : 'false';
        }
        
        if ($paymentExternalKey) {
            $queryData['paymentExternalKey'] = $paymentExternalKey;
        }
        
        if ($transactionExternalKey) {
            $queryData['transactionExternalKey'] = $transactionExternalKey;
        }
        
        if ($this->getAccountId() === null) {
            throw new \RuntimeException("The account id is not defined for this invoice");
        }
        
        $query = $this->makeQuery($queryData);
        $response = $this->createRequest(
            Client::PATH_INVOICES.'/charges/'.$this->getAccountId().$query, 
            $user, 
            $reason, 
            $comment,
            $headers,
            \json_encode($this->getItems())
            );
        
        try {
            /** @var Type\InvoiceItemAttributes|null $items */
            $items = $this->getFromBody(Type\InvoiceItemAttributes::class, $response);
            $queryInvoice = new Invoice($this->logger);
            $queryInvoice->setInvoiceId($items[0]->getInvoiceId());
            $object = $queryInvoice->get(true, $headers);
            
        } catch (Exception $e) {
            $this->logger->error($e);

            return null;
        }

        return $object;
    }

    /**
     * Returns the base uri for the current object
     *
     * @return string
     */
    protected function baseUri()
    {
        return Client::PATH_INVOICES.'/'.$this->getInvoiceId();
    }

    use CustomFieldTrait;
    use TagTrait;
}
