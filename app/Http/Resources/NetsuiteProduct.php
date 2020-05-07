<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use NetSuite\Classes\{
	PricingMatrix,
	RecordRef,
	InventoryItemLocations
};

class NetsuiteProduct extends JsonResource
{
	const CUSTOM_FIELD_MAP = [
        ['label' => 'eccn', 'id' => 1],
        ['label' => 'endDate', 'id' => 96],
        ['label' => 'netsuiteCategory', 'id' => 2],
        ['label' => 'ccats', 'id' => 441],
        ['label' => 'productSizing', 'id' => 440],
        ['label' => 'startDate', 'id' => 95]
    ];
    const REMOVE_KEYS = ['locationsList', 'memberList', 'customFieldList', 'siteCategoryList'];

    private $data;
    private $key;
    private $item;

    public function toArray($request)
    {
    	return $this->parseInitial();
    }

    private function parseInitial()
    {
    	$this->data = [];
        foreach($this->resource as $key => $item) {
            if($item !== null) {
                $this->key = $key;
        		$this->item = $item;    
                $this->parse();
                $this->data[$key] = $this->item;
            }
        }
        $this->cleanupData();
        return $this->data;
    }

    private function cleanupData()
    {
    	$this->appendCustomData();
    	$this->unsetRemoveKeys();
    }

    private function appendCustomData()
    {
    	$this->data = array_merge($this->data, $this->data['customFieldList']);
    }

    private function unsetRemoveKeys()
    {
        foreach(self::REMOVE_KEYS as $key) {
            unset($this->data[$key]);
        }
    }

    private function getCustomFieldLabel($id) 
    {
        $customField = collect(self::CUSTOM_FIELD_MAP)->firstWhere('id', $id);
        return optional($customField)['label'];
    }

    private function parse()
    {
    	$this->parseCustomFields();
    	$this->parseRecords();
    	$this->parsePricing();
    	$this->parseLocations();
    }

    private function parseCustomFields()
    {
    	if($this->key == 'customFieldList') {
            $this->item = collect($this->item->customField)->mapWithKeys(function($customField){
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

    private function parseRecords()
    {
    	if($this->item instanceof RecordRef) {
            $this->item = $this->item->name;
        }
    }

    private function parsePricing()
    {
    	if($this->item instanceof PricingMatrix) {
            $this->item = collect($this->item->pricing)->mapWithKeys(function($price){
            	$key = Str::camel(strtolower($price->priceLevel->name));
            	$value = $price->priceList->price[0]->value;
                return [$key => $value];
            })->toArray();
        }
    }

    private function parseLocations()
    {
    	if($this->key == 'locationsList') {
    		$warehouse = collect($this->item->locations)->firstWhere('location', 'Warehouse');
			if($warehouse instanceof InventoryItemLocations) {
	            $this->data['quantityOnHand'] = $warehouse->quantityOnHand;
	            $this->data['quantityBackOrdered'] = $warehouse->quantityBackOrdered;
	        }
        }
    }
}