<?php

namespace App\Services\NetSuite\Inventory;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\NetSuite\Service;
use NetSuite\Classes\{
    SearchBooleanField,
    ItemSearchBasic,
    SearchRequest,
    SearchMoreWithIdRequest,
    SearchDateField,
    SearchDateFieldOperator,
    RecordRef,

    PricingMatrix
};

use Closure;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\NetsuiteProduct;

class Search extends Service {

    const PER_PAGE = 200;
    const NS_PRODUCT_DATEFORMAT = 'm/d/Y';
    const APP_TIMEZONE = "America/Chicago";

    const CUSTOM_FIELD_MAP = [
        ['label' => 'eccn', 'id' => 1],
        ['label' => 'endDate', 'id' => 96],
        ['label' => 'netsuiteCategory', 'id' => 2],
        ['label' => 'ccats', 'id' => 441],
        ['label' => 'productSizing', 'id' => 440],
        ['label' => 'startDate', 'id' => 95]
    ];

    protected $previousSearchId;
    protected $totalPages = null;
    protected $search;
    protected $request;
    protected $response;
    protected $page = 1;
    protected $fromDate;

    public function __construct(string $fromDate = null)
    {
        parent::__construct();
        if($fromDate) {
            $this->setFromDate($fromDate);
        }
    }

    // public function search(Closure $callback) {
    //     if(!$this->fromDate) {
    //         $this->setFromDate();
    //     }
    //     do {
    //         $records = $this->searchAction();
    //         if($records) {
    //             $callback($records);
    //         }
    //     } while($this->inLoop());
    // }

    public function search() {
        if(!$this->fromDate) {
            $this->setFromDate();
        }
        do {
            $tmp = $this->searchAction();
            dd($tmp[0]);
            // $records = $this->searchAction();
            // if($records) {
            //     $callback($records);
            // }
        } while($this->inLoop());
    }


    public function setFromDate($dateString = null)
    {
        $this->fromDate = ($dateString) ? (new \DateTime($dateString))->format('c') : (new \DateTime())->sub(new \DateInterval('P1D'))->format('c');
        $this->setParameters();
        $this->init();
    }

    protected function searchAction()
    {
        try {
            $response = $this->runSearch();
            return $this->handleResponse($response);
        } catch(\Exception $e) {
            print_r($e->getMessage());
            $this->searchAction();
        }
    }

    private function runSearch()
    {
        if($this->page > 1) {
            return $this->paginatedSearch($this->request);
        } else {
            return $this->initialSearch($this->request);
        }
    }

    public function initialSearch()
    {
        return $this->service->search($this->request);
    }

    public function paginatedSearch()
    {
        if($this->inLoop()) {
            $this->setPaginatedRequest();
            return $this->service->searchMoreWithId($this->request);
        }
    }

    protected function handleResponse($response)
    {
        $searchResult = $response->searchResult;
        if($searchResult->status->isSuccess) {
            $this->totalPages = $searchResult->totalPages;
            $this->previousSearchId = $searchResult->searchId;
            $this->page = $searchResult->pageIndex+1;
            return $this->parseRecords($searchResult->recordList->record);
        }
        return null;
    }

    protected function init() : void
    {
        $this->setParameters();
        $this->initRequest();
    }

    protected function setParameters()
    {
        $searchBooleanField = new SearchBooleanField();
        $searchBooleanField->searchValue = true;
        $searchDateField = new SearchDateField();
        $searchDateField->searchValue = $this->fromDate;
        $searchDateField->operator = SearchDateFieldOperator::onOrAfter;
        $this->search = new ItemSearchBasic;
        $this->search->isAvailable = $searchBooleanField;
        $this->search->lastModifiedDate = $searchDateField;
        $this->service->setSearchPreferences(false, self::PER_PAGE);
    }

    public function initRequest()
    {
        $this->request = new SearchRequest();
        $this->request->searchRecord = $this->search;
    }

     public function setPaginatedRequest()
    {
        $this->request = new SearchMoreWithIdRequest();
        $this->request->searchId = $this->previousSearchId;
        $this->request->pageIndex = $this->page;
    }

    public function getTotalPages()
    {
        return $this->totalPages;
    }

    public function getCurrentPage()
    {
        return $this->page;
    }

    public function getLastPage()
    {
        return $this->page-1;
    }

    public function inLoop()
    {
        if($this->totalPages != null) {
            return $this->page <= $this->totalPages;
        }
        return true;
    }








    public function parseRecords($records)
    {
        // return $records;
        $records = collect($records);
        return $records->map(function($record){
            $pricing = optional($this->parsePricing($record->pricingMatrix));
            $quantity = optional($this->parseQuantities($record->locationsList));
            $customFields = optional($this->parseCustomFields($record->customFieldList));
            return [
                "nsid" => $record->internalId,
                "active_in_webstore" => $record->isOnline ? "Yes" : "No",
                "inactive" => $record->isInactive ? "Yes" : "No",
                "ns_product_category" => $customFields['netsuiteCategory'],
                "startdate" => $this->parseTime($customFields['startDate']),
                "enddate" => $this->parseTime($customFields['endDate']),
                "sku" => $record->itemId ?? '',
                "featured_description" => $record->salesDescription ?? '',
                "UPC" => $record->upcCode ?? '',
                "description" => $record->purchaseDescription ?? '',
                "ECCN" => $customFields['eccn'],
                "CCATS" => $customFields['ccats'],
                "online_price" => $pricing['onlinePrice'],
                "map" => $pricing['map'],
                "total_quantity_on_hand" => array_sum($quantity['total_quantity_on_hand']),
                "taxable" => $record->isTaxable ? 'Yes' : 'No',
                "weight" => $record->weight ?? 0.00,
                "weight_units" => str_replace('_', '', $record->weightUnit),
                "authdealerprice" => $pricing['authorizedDealer'],
                "buyinggroupprice" => $pricing['buyingGroup'],
                "dealerprice" => $pricing['dealer'],
                "dealerdistprice" => $pricing['dealerDistributor'],
                "disprice" => $pricing['distributor'],
                "dropshipprice" => $pricing['dropShip'],
                "govprice" => $pricing['government'],
                "msrp" => $pricing['msrp'],
                "specials" => $pricing['specials'],
                "onlineprice" => $pricing['onlinePrice'],
                "backordered" => array_sum($quantity['backordered']),
                "product_sizing" => $customFields['productSizing'],

            ];
        });
        
        // return NetsuiteProduct::collection($records);
    }


    private static function parseTime($timeString)
    {
        if($timeString) {
            return Carbon::parse($timeString)->setTimezone(self::APP_TIMEZONE)->format(self::NS_PRODUCT_DATEFORMAT);
        }
        return '';
    }

    private function parsePricing($pricingMatrix)
    {
        if($pricingMatrix instanceof PricingMatrix) {
            $pricingMatrix = collect($pricingMatrix->pricing)->mapWithKeys(function($price){
                $key = Str::camel(strtolower($price->priceLevel->name));
                $value = $price->priceList->price[0]->value;
                return [$key => $value];
            })->toArray();
            return $pricingMatrix;
        }
        return [];
    }

    private function parseQuantities($locationsList)
    {
        $quantityOnHand = collect($locationsList->locations)->mapWithKeys(function($location){
            $key = $location->location;
            $value = $location->quantityOnHand ?? 0.0;
            return [$key => $value];
        })->toArray();

        $quantityBackOrdered = collect($locationsList->locations)->mapWithKeys(function($location){
            $key = $location->location;
            $value = $location->quantityBackOrdered ?? 0.0;
            return [$key => $value];
        })->toArray();

        return [
            'total_quantity_on_hand' => $quantityOnHand,
            'backordered' => $quantityBackOrdered
        ];
    }

    private function getCustomFieldLabel($id) 
    {
        $customField = collect(self::CUSTOM_FIELD_MAP)->firstWhere('id', $id);
        return optional($customField)['label'];
    }

    private function parseCustomFields($customFieldList)
    {
        return collect($customFieldList->customField)->mapWithKeys(function($customField){
            $k = $this->getCustomFieldLabel($customField->internalId);
            $v = $customField->value;
            if($k == 'netsuiteCategory') {
                $v = $v->name;
            }
            return [$k => $v];
        })->filter(function($customField, $key){
            return $key != '';
        })->toArray();
    }

}
