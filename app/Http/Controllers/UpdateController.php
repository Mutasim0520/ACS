<?php

namespace App\Http\Controllers;
use App\Journal;
use App\Ledger_categorie;
use App\User as User;
use Carbon\Carbon;
use DeepCopy\f008\B;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Supplier as Suppliers;
use App\Categorie as Category;
use App\Color as Colors;
use App\Size as Sizes;
use App\Product as Products;
use App\Buyer as Buyers;
use App\Purchase as Purchases;
use App\Sale as Sales;
use App\Sub_categorie as SubCategory;
use App\Purchase_historie as History;
use App\Sales_historie as Sale_History;
use Mockery\CountValidator\Exception;
use App\Ledger as Ledgers;
use App\Journal as Journals;
use App\Ledger_categorie as Ledger_category;
use App\Accounts_purchase_historie as Accounts_purchase_history;
use App\Accounts_sale_historie as Accounts_sale_history;
use App\User as Admins;

class UpdateController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function setNewPurchaseProperty($input){
        $purchase = Purchases::where('id',$input->purchase_id)->first();
        $purchase->reference = $input->reference;
        $purchase->supplier_id = $input->supplierId;
        $purchase->date = Carbon::parse($input->date);
        $purchase->save();
        $history = new History();
        $history->history = json_encode($input);
        $history->purchase_id = $purchase->id;
        $history->save();
    }

    public function setNewSaleProperty($input){
        $sale = Sales::where('id',$input->sale_id)->first();
        $sale->reference = $input->reference;
        $sale->buyer_id = $input->buyerId;
        $sale->date = Carbon::parse($input->date);
        $sale->save();

        $history = new Sale_History();
        $history->history = json_encode($input);
        $history->sale_id = $sale->id;
        $history->save();
    }

    public function updatePurchase(Request $request){
        $user = User::where('api_token',$request->header('api-token'))->first();
        try{

            $input = json_decode($request->purchase);
            $purchase = Purchases::with(['purchase_historie' => function($query){
                return $query->orderBy('created_at','DESC')->first();
            }])->where('id', $input->purchase_id)->first();


            $this->setnewPurchaseProperty($input);
            $this->updatePurchasedProduct($input);
            $response = [
                'message' => 'updated'
            ];
            return response(json_encode($response,201));
        }catch (Exception $e){
            return response("error",500);
        }
    }

    public function updateSale(Request $request){
        $user = User::where('api_token',$request->header('api-token'))->first();
        try{

            $input = json_decode($request->sale);
            $sale = Sales::with(['sales_historie' => function($query){
                return $query->orderBy('created_at','DESC')->first();
            }])->where('id', $input->sale_id)->first();


            $this->setnewSaleProperty($input);
            $this->updateSoldProduct($input);
            $response = [
                'message' => 'updated'
            ];
            return response(json_encode($response,201));
        }catch (Exception $e){
            return response("error",500);
        }
    }

    protected function updatePurchasedProduct($input){
        try{
            $purchase = Purchases::with(['purchase_historie' => function($query){
                return $query->orderBy('created_at','DESC')->first();
            }])->where('id',$input->purchase_id)->first();
            $new_prd = [];
            $old_prd = [];
            foreach ($input->products as $item){
                if(!$item->id){
                    $new_prd[] = $item;
                }
                else{
                    $old_prd[] = $item;
                }
            }
            //return json_encode($old_prd);

            $supplier = Suppliers::where('id',$input->supplierId)->first();

            foreach ($old_prd as $item){
                $category = Category::where('id',$item->category)->first();
                $sub_category = SubCategory::where('id',$item->sub_category)->first();
                $meta = $category->name.'_'.$sub_category->name.'_'.$item->name.'_'.$supplier->name.'_'.$input->reference;
                $product = Products::where('id',$item->id)->first();
                $product->name = $item->name;
                $product->meta = $meta;
                $product->categorie_id = $item->category;
                $product->sub_categorie_id = $item->sub_category;
                $product->purchase_id = $purchase->id;
                $stock = $this->countTotal($item->colors);
                $product->stock = $stock;
                $product->initial_stock = $stock;
                $product->save();
                $this->updateAmounts($product,$item->colors);
            }
            if(sizeof($new_prd)>0){
                foreach ($new_prd as $item){
                    $category = Category::where('id',$item->category)->first();
                    $sub_category = SubCategory::where('id',$item->sub_category)->first();
                    $meta = $category->name.'_'.$sub_category->name.'_'.$item->name.'_'.$supplier->name.'_'.$input->reference;
                    $product = new Products();
                    $product->name = $item->name;
                    $product->meta = $meta;
                    $product->categorie_id = $item->category;
                    $product->sub_categorie_id = $item->sub_category;
                    $product->purchase_id = $purchase->id;
                    $stock = $this->countTotal($item->colors);
                    $product->stock = $stock;
                    $product->initial_stock = $stock;
                    $product->save();
                    $product = Products::orderBy('id','DESC')->first();
                    $this->setAmounts($product,$item->colors);
                }

                if($purchase->status != '0') $purchase->status = "extended";
                if($purchase->payment_status)$purchase->payment_status = "extended";
                $purchase->save();
            }

        }catch (Exception $e){
            return response("Internal Server Error",500);
        }

    }

    protected function updateSoldProduct($input){
        try{
            $sale = Sales::with(['sales_historie' => function($query){
                return $query->orderBy('created_at','DESC')->first();
            }])->where('id',$input->sale_id)->first();

            $old_history = json_decode($sale->sales_historie[0]->history);

            foreach ($old_history->products as $item){
                $product = Products::where('id',$item->id)->first();
                $amount_to_add = $this->adjustProductPreviousStock($product,$item->colors);
                $product->sale()->save($sale,['total_amount' => $amount_to_add]);
            }

            $prd = $input->products;

            foreach ($prd as $item){
                $product = Products::where('id',$item->id)->first();
                $amount_to_reduce = $this->updateProductStock($product,$item->colors);
                $product->sale()->save($sale,['total_amount' => $amount_to_reduce]);
            }
            if(sizeof($prd > sizeof($old_history->products))){
                if($sale->status != '0') $sale->status = "extended";
                if($sale->payment_status)$sale->payment_status = "extended";
            }
            elseif (sizeof($prd == sizeof($old_history->products))){
                foreach ($prd as $new_prd){
                    foreach ($old_history->products as $old_prd){
                        if($new_prd->id != $old_prd->id){
                            if($sale->status != '0') $sale->status = "extended";
                            if($sale->payment_status)$sale->payment_status = "extended";
                        }
                    }
                }
            }

        }catch (Exception $e){
            return response("Internal Server Error",500);
        }

    }

    public function countTotal($amount){
        $total = 0;
        foreach ($amount as $item){
            foreach ($item->sizes as $quant){
                $total = $total + intval($quant->quantity);
            }
        }
        return $total;
    }

    public function updateAmounts($product,$items){
        foreach ($items as $color){
            foreach ($color->sizes as $size){
                $new = Sizes::where('id',$size->id)->first();
                $new->product()->sync([$product->id => ['color_id' => $color->id , 'quantity' => $size->quantity]]);
            }
        }
    }

    public function setAmounts($product,$items){
        foreach ($items as $color){
            foreach ($color->sizes as $size){
                $new = Sizes::where('id',$size->id)->first();
                $new->product()->save($product,['color_id' => $color->id , 'quantity' => $size->quantity]);
            }
        }
    }

    public function createNewLedger($ledger){
        $ledger = ucwords($ledger->company);
        $signal = Ledgers::where('name',$ledger)->first();
        if(!$signal){
            $newLedger = new Ledgers();
            $newLedger->name = $ledger;
            $newLedger->save();
            $newLedger = Ledgers::orderBy('id','DESC')->first();
        }
    }

    public function updatePricePurchase(Request $request){
        $user = User::where('api_token',$request->header('api-token'))->first();
        try{
            $input = json_decode($request->purchase);
            $purchase = Purchases::with(['accounts_purchase_historie' => function ($query){
                return $query->orderBy('id','DESC')->first();
            },'product'])->find($input->purchaseId);

            $purchase->transport = $input->transport;
            $purchase->vat = $input->vat;
            $purchase->discount = $input->discount;
            $purchase->labour = $input->labour;
            $purchase->other = $input->others;
            $purchase->status = "complete";

            $history = json_decode( $purchase->accounts_purchase_historie[0]->history);
            $transport = json_decode($this->compareHistory($history,$input,'transport'));
            $labour = json_decode($this->compareHistory($history,$input,'labour'));
            $others = json_decode($this->compareHistory($history,$input,'others'));

            $flag = "paid";
            $stock_value = 0;
            $due = 0;
            foreach ($input->products as $item){
                $stock_value = $stock_value+$item->total;
            }
            if($input->payment_category ==  2 || $input->payment_category == 3){
                $flag = "due";
                if($input->payment_category ==  2){
                    $due = $due+$stock_value;
                }
                else if($input->payment_category ==  3){
                    $payment = $input->partial;
                    if($input->payment_type == 1){
                        if($purchase->advance) $due = $due+($stock_value-intval($payment->cash)-intval($purchase->advance->amount));
                        else $due = $due+($stock_value-intval($payment->cash));
                    }
                    else if($input->payment_type == 2){
                        if($purchase->advance) $due = $due+($stock_value-intval($payment->check)-intval($purchase->advance->amount));
                        else $due = $due+($stock_value-intval($payment->check));
                    }
                    else if($input->payment_type == 3){
                        if($purchase->advance) $due = $due+($stock_value-intval($payment->check)-intval($payment->cash)-intval($purchase->advance->amount));
                        else $due = $due+($stock_value-intval($payment->check)-intval($payment->cash));
                    }
                }
            }

            $purchase->payment_status = $flag;
            $purchase->due = $due;
            $purchase->total_value = $stock_value;
            $purchase->save();

            $hist = new Accounts_purchase_history();
            $hist->history = json_encode($input);
            $hist->purchase_id = $input->purchaseId;
            $hist->save();

            $supplier = $purchase->supplier;
            $old_journal = Journals::where('purchase_id',$purchase->id)->get();
            $purchase = Purchases::with('supplier','product')->where('id',$input->purchaseId)->first();
                $total = 0;
                //return json_encode($old_journal);

            foreach ($old_journal as $item){
                if($item->account == 'purchase'){
                    try{
                        $this->deleteJournalEntry($item->id);
                    }catch (Exception $e){
                        return $e;
                    }
                }
            }
            $paid_value = $stock_value-$due;
            $this->setPSIntoJournal($input , $user , 'purchase' , $purchase, $paid_value,"new");

                if($input->transport){
                    if($transport->change != "none"){
                        foreach ($old_journal as $item){
                            if($item->account == 'transport'){
                                $this->deleteJournalEntry($item->id);
                            }
                        }
                        $narration = "Transport cost of BDT ".$purchase->transport;
                        $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'transport',$purchase->date);
                        $this->setIntoJournal($supplier->company,'Dr',$purchase->transport,$journal);
                        $this->setIntoJournal('Cash',$supplier->company,'Cr',$purchase->transport,$journal);

                        $narration = "Transport cost of BDT ".$purchase->transport." for purchasing product from $supplier->company";
                        $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'transport',$purchase->date);
                        $this->setIntoJournal('Transport','Dr',$purchase->transport,$journal);
                        $this->setIntoJournal($supplier->company,'Cr',$purchase->transport,$journal);
                    }
                }
                if($input->labour){
                    if($labour->change != "none"){
                        foreach ($old_journal as $item){
                            if($item->account == 'labour'){
                                $this->deleteJournalEntry($item->id);
                            }
                        }
                        $narration = "Labour cost of BDT ".$purchase->labour;
                        $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'labour',$purchase->date);
                        $this->setIntoJournal($supplier->company,'Dr',$purchase->labour,$journal);
                        $this->setIntoJournal('Cash','Cr',$purchase->labour,$journal);

                        $narration = "Labour cost of BDT ".$purchase->labour." for purchasing product from $supplier->company";
                        $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'labour',$purchase->date);
                        $this->setIntoJournal('Labour','Dr',$purchase->labour,$journal);
                        $this->setIntoJournal($supplier->company,'Cr',$purchase->labour,$journal);
                    }
                }
                if($input->others){
                    if($others->change != "none"){
                        foreach ($old_journal as $item){
                            if($item->account == 'others'){
                                $this->deleteJournalEntry($item->id);
                            }
                        }
                        $narration = "Others cost of BDT ".$purchase->other;
                        $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'others',$purchase->date);
                        $this->setIntoJournal($supplier->company,'Dr',$purchase->other,$journal);
                        $this->setIntoJournal('Cash','Cr',$purchase->other,$journal);

                        $narration = "Others cost of BDT ".$purchase->other." for purchasing product from $supplier->company";
                        $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'others',$purchase->date);
                        $this->setIntoJournal('Other','Dr',$purchase->other,$journal);
                        $this->setIntoJournal($supplier->company,'Cr',$purchase->other,$journal);
                    }
                }
                $this->changeAccountStatusOfProduct($input->products);

                $response = [
                    'message' => 'updated'
                ];
                return response(json_encode($response,201));

        }catch (Exception $e){
            error_reporting($e);
        }

    }

    public function updatePriceSale(Request $request){
        $user = User::where('api_token',$request->header('api-token'))->first();
        try{
            $input = json_decode($request->sale);
            $sale = Sales::with(['accounts_sale_historie' => function ($query){
                return $query->orderBy('id','DESC')->first();
            },'product'])->find($input->saleId);

                $flag = "paid";
            $stock_value = 0;
            $due = 0;
            foreach ($input->products as $item){
                $stock_value = $stock_value+$item->total;
            }
            if($input->payment_category ==  2 || $input->payment_category == 3){
                $flag = "due";
                if($input->payment_category ==  2){
                    $due = $due+$stock_value;
                }
                else if($input->payment_category ==  3){
                    $payment = $input->partial;
                    if($input->payment_type == 1){
                        if($purchase->advance) $due = $due+($stock_value-intval($payment->cash)-intval($purchase->advance->amount));
                        else $due = $due+($stock_value-intval($payment->cash));
                    }
                    else if($input->payment_type == 2){
                        if($purchase->advance) $due = $due+($stock_value-intval($payment->check)-intval($purchase->advance->amount));
                        else $due = $due+($stock_value-intval($payment->check));
                    }
                    else if($input->payment_type == 3){
                        if($purchase->advance) $due = $due+($stock_value-intval($payment->check)-intval($payment->cash)-intval($purchase->advance->amount));
                        else $due = $due+($stock_value-intval($payment->check)-intval($payment->cash));
                    }
                }
            }

            $sale->status = "complete";
            $sale->payment_status = $flag;
            $sale->due = $due;
            $sale->discount = $input->discount;
            $sale->total_value = $stock_value;
            $sale->save();

            $hist = new Accounts_sale_history();
            $hist->history = json_encode($input);
            $hist->sale_id = $input->saleId;
            $hist->save();

            $supplier = $sale->supplier;
            $old_journal = Journals::where('sale_id',$sale->id)->get();
            $sale = Sales::with('buyer','product')->where('id',$input->saleId)->first();
            foreach ($old_journal as $item){
                if($item->account == 'sale'){
                    try{
                        $this->deleteJournalEntry($item->id);
                        }catch (Exception $e){
                        return $e;
                        }
                    }
                }

            $paid_value = $stock_value-$due;
            $this->setPSIntoJournal($input , $user , 'sale' , $sale, $paid_value,"new");

            $this->updateSalePriceOfProduct($input->products,$sale);

                $response = [
                    'message' => 'updated'
                ];
                return response(json_encode($response,201));
        }catch (Exception $e){
            error_reporting($e);
        }

    }

    protected function compareHistory($history,$input,$item){
        $obj = new \stdClass();

        if($input->{$item}== $history->{$item}){
            $obj->change = 'none';
        }
        elseif($input->{$item} > $history->{$item}){
            $obj->change = 'increase';
            $obj->old = $history->{$item};
            $obj->new = $input->{$item};
            $obj->difference = $input->{$item}-$history->{$item};

        }
        elseif($input->{$item} < $history->{$item}){
            $obj->change = 'decrease';
            $obj->old = $history->{$item};
            $obj->new = $input->{$item};
            $obj->difference = $history->{$item} - $input->{$item};
        }
        return json_encode($obj);

    }

    protected function updateSalePriceOfProduct($products,$sale){
        foreach ($products as $item){
            $product = Products::find($item->id);
            $product->sale()->updateExistingPivot($sale,['price' => $item->price]);
        }

    }

    public function addPaymentToPurchase(Request $request){
        $user = User::where('api_token',$request->header('api-token'))->first();
        try{
            $input = json_decode($request->purchase);
            $purchase = Purchases::find($input->purchaseId);
            if($purchase->payment_status == 'paid'){
                $response = [
                    'message' => 'already completed'
                ];
                return response(json_encode($response,304));
            }
            else{
                $due = intval($purchase->due)-intval($input->paid);
                if($due == 0) $purchase->payment_status = "paid";
                else $purchase->payment_status = "due";
                $purchase->due = $due;
                $purchase->save();

                $purchase = Purchases::with('supplier','product')->where('id',$input->purchaseId)->first();
                $this->setPSIntoJournal($input , $user , 'purchase' , $purchase, $input->paid,"old");

                $response = [
                    'message' => 'created'
                ];
                return response(json_encode($response,201));
            }
        }catch (Exception $e){
            error_reporting($e);
        }
    }

    public function addPaymentToSale(Request $request){
        $user = User::where('api_token',$request->header('api-token'))->first();
        try{
            $input = json_decode($request->sale);
            $sale = Sales::find($input->saleId);
            $due = intval($sale->due)-intval($input->paid);

            if($due == 0) $sale->payment_status = "paid";
            else $sale->payment_status = "due";
            $sale->due = $due;
            $sale->save();

            $sale = Sales::with('buyer','product')->where('id',$input->saleId)->first();
            $this->setPSIntoJournal($input , $user , 'sale' , $sale, $input->paid,"old");

            $response = [
                'message' => 'created'
            ];
                return response(json_encode($response,201));
        }catch (Exception $e){
            error_reporting($e);
        }
    }

    protected function deleteJournalEntry($id){
        try{
            $journal = Journals::find($id);
            $journal->delete();
        }catch (Exception $e){
            return $id;
        }
    }

    public function setIntoJournal($account,$type,$value,$journal){
        $ledger = Ledgers::where('name',($account))->first();
        if(!$ledger){
            $ledger = new Ledgers();
            $ledger->name = $account;
            $ledger->save();
        }
        $ledger = Ledgers::where('name',($account))->first();
        $journal->ledger()->save($ledger,['account_type' => $type,'value' => $value]);
    }

    public function setPSIntoJournal($item,$user,$action_type,$action,$paid_value,$action_status){
        $category = $item->payment_category;
        $type = $item->payment_type;
        $discount = $action->discount;
        if($action_type == 'purchase'){
            $supplier = $action->supplier;
            $due = intval($action->total_value)- $paid_value;
            if($action_status == "new"){
                $narration = "Purchased goods from $supplier->company of BDT $action->total_value  and got discount of BDT $discount. Purchase ID: $action->id";
            }
            else{
                $narration = "Paid due payment to Purchase ID: $action->id, BDT $paid_value.";
            }

            $journal = new Journals();
            $journal->user_id = $user->id;
            $journal->purchase_id = $action->id;
            if($action_status == "new"){
                $journal->date = $action->date;
                $journal->account = 'purchase';
            }
            else {
                $journal->date = Carbon::today();
                $journal->account = 'add payment purchase';
            }
            $journal->narration = $narration;
            $journal->save();
            $journal = Journals::orderBy('id','DESC')->first();

            $narration_2 ="";
            if($action_status == "new"){
                $this->setIntoJournal('Purchase','Dr',$action->total_value,$journal);
                if($discount){
                    $this->setIntoJournal($supplier->company,'Cr',$action->total_value-intval($discount),$journal);
                    $this->setIntoJournal('Discount','Cr',$discount,$journal);
                }
                else $this->setIntoJournal($supplier->company,'Cr',$action->total_value,$journal);
            }
            else{
                $this->setIntoJournal('Purchase','Dr',$paid_value,$journal);
                $this->setIntoJournal($supplier->company,'Cr',$paid_value,$journal);
            }

            $journal_2 = new Journals();
            $journal_2->user_id = $user->id;
            $journal_2->purchase_id = $action->id;
            if($action_status == "new"){
                $journal_2->date = $action->date;
                $journal_2->account = 'purchase';
            }
            else {
                $journal_2->date = Carbon::today();
                $journal_2->account = 'add payment purchase';
            }
            $journal_2->save();
            $journal_2 = Journals::orderBy('id','DESC')->first();

            if($discount) $total_company = $action->total_value-intval($discount);
            else $total_company = $action->total_value;

            if($category == '3'){
                $value = $item->partial;
                $check = $item->check;
                if($type == '3'){
                    if($action_status == "new"){
                        $narration_2 = "Purchased goods from $supplier->company of BDT $action->total_value. Paid $supplier->company BDT $value->cash in cash and BDT $value->check in check at $check. Due $due";
                        $this->setIntoJournal($supplier->company,'Dr',$total_company,$journal_2);
                        $this->setIntoJournal($check,'Cr',$value->check,$journal_2);
                        $this->setIntoJournal('Cash','Cr',$value->cash,$journal_2);
                        $this->setIntoJournal('Accounts Payable','Cr',$due,$journal_2);
                    }
                    else{
                        $narration_2 = "Paid due payment to Purchase ID: $action->id, $supplier->company BDT $value->cash in cash and BDT $value->check in check at $check. Due $due";
                        $this->setIntoJournal($supplier->company,'Dr',$paid_value,$journal_2);
                        $this->setIntoJournal($check,'Cr',$value->check,$journal_2);
                        $this->setIntoJournal('Cash','Cr',$value->cash,$journal_2);
                    }
                }

                elseif ($type == '2'){
                    if($action_status == "new"){
                        $narration_2 = "Purchased goods from $supplier->company of BDT $action->total_value. Paid $supplier->company BDT $value->check in check at $check. Due $due";
                        $this->setIntoJournal($supplier->company,'Dr',$total_company,$journal_2);
                        $this->setIntoJournal($check,'Cr',$value->check,$journal_2);
                        $this->setIntoJournal('Accounts Payable','Cr',$due,$journal_2);
                    }
                    else{
                        $narration_2 = "Paid due payment to Purchase ID: $action->id, $supplier->company BDT $paid_value in check at $check. Due $due";
                        $this->setIntoJournal($supplier->company,'Dr',$paid_value,$journal_2);
                        $this->setIntoJournal($check,'Cr',$paid_value,$journal_2);
                    }
                }

                elseif ($type == '1'){
                    if($action_status == "new"){
                        $narration_2 = "Purchased goods from $supplier->company of BDT $action->total_value. Paid $supplier->company BDT $value->cash in cash. Due $due";
                        $this->setIntoJournal($supplier->company,'Dr',$total_company,$journal_2);
                        $this->setIntoJournal('Cash','Cr',$value->cash,$journal_2);
                        $this->setIntoJournal('Accounts Payable','Cr',$due,$journal_2);
                    }
                    else{
                        $narration_2 = "Purchased goods from $supplier->company of BDT $paid_value. Paid $supplier->company BDT $value->cash in cash. Due $due";
                        $this->setIntoJournal($supplier->company,'Dr',$paid_value,$journal_2);
                        $this->setIntoJournal('Cash','Cr',$paid_value,$journal_2);
                    }
                }
            }

            elseif ($category == '1'){
                $check = $item->check;
                if ($type == '2'){
                    if($action_status == "new"){
                        $narration_2 = "Purchased goods from $supplier->company of BDT $action->total_value. Paid $supplier->company BDT $total_company in check at $check.";
                        $this->setIntoJournal($supplier->company,'Dr',$total_company,$journal_2);
                        $this->setIntoJournal($check,'Cr',$total_company,$journal_2);
                    }
                    else{
                        $narration_2 = "Paid due payment to Purchase ID: $action->id, $supplier->company BDT $paid_value in check at $check.";
                        $this->setIntoJournal($supplier->company,'Dr',$paid_value,$journal_2);
                        $this->setIntoJournal($check,'Cr',$paid_value,$journal_2);
                    }
                }
                elseif ($type == '1'){
                    if($action_status == "new"){
                        $narration_2 = "Purchased goods from $supplier->company of BDT $action->total_value. Paid $supplier->company BDT $total_company in cash.";
                        $this->setIntoJournal($supplier->company,'Dr',$total_company,$journal_2);
                        $this->setIntoJournal('Cash','Cr',$total_company,$journal_2);
                    }
                    else{
                        $narration_2 = "Paid due payment to Purchase ID: $action->id, $supplier->company BDT $paid_value in cash.";
                        $this->setIntoJournal($supplier->company,'Dr',$paid_value,$journal_2);
                        $this->setIntoJournal('Cash','Cr',$paid_value,$journal_2);
                    }
                }
                elseif($type == '3'){
                    $value = $item->partial;
                    if($action_status == "new"){
                        $narration_2 = "Purchased goods from $supplier->company of BDT $action->total_value. Paid $supplier->company BDT $value->cash in cash and BDT $value->check in check at $check.";
                        $this->setIntoJournal($supplier->company,'Dr',$total_company,$journal_2);
                        $this->setIntoJournal('Cash','Cr',$value->cash,$journal_2);
                        $this->setIntoJournal($check,'Cr',$value->check,$journal_2);
                    }
                    else{
                        $narration_2 = "Paid due payment to Purchase ID: $action->id, $supplier->company BDT $value->cash in cash and BDT $value->check in check at $check.";
                        $this->setIntoJournal($supplier->company,'Dr',$paid_value,$journal_2);
                        $this->setIntoJournal('Cash','Cr',$value->cash,$journal_2);
                        $this->setIntoJournal($check,'Cr',$value->check,$journal_2);
                    }
                }
            }

            $journal_2->narration = $narration_2;
            $journal_2->save();

        }
        else{
            $buyer = $action->buyer;
            $due = intval($action->total_value)- $paid_value;
            if($action_status == "new"){
                $narration = "Sold products to $buyer->company of BDT $action->total_value  and got discount of BDT $discount. Sale ID: $action->id";
            }
            else{
                $narration = "Got due payment of Sale ID: $action->id, BDT $paid_value.";
            }
            $journal = new Journals();
            $journal->user_id = $user->id;
            $journal->sale_id = $action->id;
            if($action_status == "new"){
                $journal->date = $action->date;
                $journal->account = 'sale';
            }
            else {
                $journal->date = Carbon::today();
                $journal->account = 'add payment sale';
            }
            $journal->save();
            $journal = Journals::orderBy('id','DESC')->first();

            $narration_2 ="";
            if($action_status == "new"){
                $this->setIntoJournal('Sale','Cr',$action->total_value,$journal);
                if($discount){
                    $this->setIntoJournal($buyer->company,'Dr',$action->total_value-intval($discount),$journal);
                    $this->setIntoJournal('Discount','Dr',$discount,$journal);
                }
                else $this->setIntoJournal($buyer->company,'Dr',$action->total_value,$journal);
            }
            else{
                $this->setIntoJournal('Sale','Cr',$paid_value,$journal);
                $this->setIntoJournal($buyer->company,'Dr',$paid_value,$journal);
            }

            $journal_2 = new Journals();
            $journal_2->user_id = $user->id;
            $journal_2->sale_id = $action->id;
            if($action_status == "new"){
                $journal_2->date = $action->date;
                $journal_2->account = 'sale';
            }
            else {
                $journal_2->date = Carbon::today();
                $journal_2->account = 'add payment sale';
            }
            $journal_2->save();
            $journal_2 = Journals::orderBy('id','DESC')->first();

            if($discount) $total_company = $action->total_value-intval($discount);
            else $total_company = $action->total_value;

            if($category == '3'){
                $value = $item->partial;
                $check = $item->check;
                if($type == '3'){
                    if($action_status == "new"){
                        $narration_2 = "Sold products to $buyer->company of BDT $action->total_value. Paid $buyer->company BDT $value->cash in cash and BDT $value->check in check at $check. Due $due";
                        $this->setIntoJournal($buyer->company,'Cr',$total_company,$journal_2);
                        $this->setIntoJournal($check,'Dr',$value->check,$journal_2);
                        $this->setIntoJournal('Cash','Dr',$value->cash,$journal_2);
                        $this->setIntoJournal('Accounts Receivable','Dr',$due,$journal_2);
                    }
                    else{
                        $narration_2 = "Paid due payment of Sale ID: $action->id of buyer: $buyer->company, BDT $value->cash in cash and BDT $value->check in check at $check. Due $due";
                        $this->setIntoJournal($buyer->company,'Cr',$paid_value,$journal_2);
                        $this->setIntoJournal($check,'Dr',$value->check,$journal_2);
                        $this->setIntoJournal('Cash','Dr',$value->cash,$journal_2);
                    }
                }

                elseif ($type == '2'){
                    if($action_status == "new"){
                        $narration_2 = "Sold products to $buyer->company of BDT $action->total_value. Paid $buyer->company BDT $value->check in check at $check. Due $due";
                        $this->setIntoJournal($buyer->company,'Cr',$total_company,$journal_2);
                        $this->setIntoJournal($check,'Dr',$value->check,$journal_2);
                        $this->setIntoJournal('Accounts Receivable','Dr',$due,$journal_2);
                    }
                    else{
                        $narration_2 = "Paid due payment of Sale ID: $action->id of buyer: $buyer->company, BDT $value->check in check at $check. Due $due";
                        $this->setIntoJournal($buyer->company,'Cr',$paid_value,$journal_2);
                        $this->setIntoJournal($check,'Dr',$value->check,$journal_2);
                    }
                }

                elseif ($type == '1'){
                    if($action_status == "new"){
                        $narration_2 = "Sold products to $buyer->company of BDT $action->total_value. Paid $buyer->company BDT $value->cash in cash. Due $due";
                        $this->setIntoJournal($buyer->company,'Cr',$total_company,$journal_2);
                        $this->setIntoJournal('Cash','Dr',$value->cash,$journal_2);
                        $this->setIntoJournal('Accounts Receivable','Dr',$due,$journal_2);
                    }
                    else{
                        $narration_2 = "Paid due payment of Sale ID: $action->id of buyer: $buyer->company, BDT $value->cash in cash. Due $due";
                        $this->setIntoJournal($buyer->company,'Cr',$paid_value,$journal_2);
                        $this->setIntoJournal('Cash','Dr',$value->cash,$journal_2);
                    }
                }
            }

            elseif ($category == '1'){
                $check = $item->check;
                if ($type == '2'){
                    if($action_status == "new"){
                        $narration_2 = "Sold products to $buyer->company of BDT $action->total_value. Paid $buyer->company BDT $total_company in check at $check.";
                        $this->setIntoJournal($buyer->company,'Cr',$total_company,$journal_2);
                        $this->setIntoJournal($check,'Dr',$total_company,$journal_2);
                    }
                    else{
                        $narration_2 = "Sold products to $buyer->company of BDT $action->total_value. Paid $buyer->company BDT $paid_value in check at $check.";
                        $this->setIntoJournal($buyer->company,'Cr',$paid_value,$journal_2);
                        $this->setIntoJournal($check,'Dr',$paid_value,$journal_2);
                    }
                }
                elseif ($type == '1'){
                    if($action_status == "new"){
                        $narration_2 = "Sold products to $buyer->company of BDT $action->total_value. Paid $buyer->company BDT $total_company in cash.";
                        $this->setIntoJournal($buyer->company,'Cr',$total_company,$journal_2);
                        $this->setIntoJournal('Cash','Dr',$total_company,$journal_2);
                    }
                    else{
                        $narration_2 = "Paid due payment of Sale ID: $action->id of buyer: $buyer->company, BDT $paid_value in cash.";
                        $this->setIntoJournal($buyer->company,'Cr',$paid_value,$journal_2);
                        $this->setIntoJournal('Cash','Dr',$paid_value,$journal_2);
                    }
                }
                elseif($type == '3'){
                    $value = $item->partial;
                    if($action_status == "new"){
                        $narration_2 = "Sold products to $buyer->company of BDT $action->total_value. Paid $buyer->company BDT $value->cash in cash and BDT $value->check in check at $check.";
                        $this->setIntoJournal($buyer->company,'Cr',$total_company,$journal_2);
                        $this->setIntoJournal('Cash','Dr',$value->cash,$journal_2);
                        $this->setIntoJournal($check,'Dr',$value->check,$journal_2);
                    }
                    else{
                        $narration_2 = "Paid due payment of Sale ID: $action->id of buyer: $buyer->company, BDT $value->cash in cash and BDT $value->check in check at $check.";
                        $this->setIntoJournal($buyer->company,'Cr',$paid_value,$journal_2);
                        $this->setIntoJournal('Cash','Dr',$value->cash,$journal_2);
                        $this->setIntoJournal($check,'Dr',$value->check,$journal_2);
                    }
                }
            }

            $journal->narration = $narration;
            $journal->save();

            $journal_2->narration = $narration_2;
            $journal_2->save();
        }
    }

//    public function updatePSIntoJournal($product,$user,$action_type,$action,$old_journal){
//        if($action_type == 'purchase'){
//            foreach ($old_journal as $item){
//                if($item->account == 'purchase'){
//                    try{
//                        $this->deleteJournalEntry($item->id);
//
//                    }catch (Exception $e){
//                        return $e;
//                    }
//                }
//            }
//            $this->setPSIntoJournal($product , $user , $action_type , $action);
//        }
//        else{
//            foreach ($old_journal as $item){
//                if($item->account == 'sale'){
//                    $this->deleteJournalEntry($item->id);
//                }
//            }
//            $this->setPSIntoJournal($item , $user , $action_type , $action);
//        }
//    }

    protected function createJournalEntry($narration,$user_id,$purchase_id,$account,$date){
        $journal = new Journals();
        $journal->user_id = $user_id;
        $journal->purchase_id = $purchase_id;
        $journal->narration = $narration;
        $journal->account = $account;
        $journal->date = $date;
        $journal->save();
        $journal = Journals::orderBy('id','DESC')->first();
        return $journal;
    }

    protected function updateProductStock($product,$items){
        $reduce = 0;
        foreach ($items as $color){
            foreach ($color->sizes as $size){
                $new = Sizes::with(['product' => function($query) use($product){
                    return $query->where('id',$product->id)->first();
                }])->where('id',$size->id)->first();
                $prd = $new->product;
                $stock = $prd[0]->pivot->quantity;
                $new_stock = $stock - $size->quantity;
                $new->product()->updateExistingPivot($product->id,['color_id' => $color->id , 'quantity' => $new_stock]);
                $reduce = $reduce+$size->quantity;
            }
            $product = Products::where('id',$product->id)->first();
            $product->stock = $product->initial_stock - $reduce;
            $product->save();
        }
        return $reduce;
    }

    protected function adjustProductPreviousStock($product,$items){
        $add = 0;
        foreach ($items as $color){
            foreach ($color->sizes as $size){
                $new = Sizes::with(['product' => function($query) use($product){
                    return $query->where('id',$product->id)->first();
                }])->where('id',$size->id)->first();
                $prd = $new->product;
                $stock = $prd[0]->pivot->quantity;
                $new_stock = $stock + $size->quantity;
                $new->product()->updateExistingPivot($product->id,['color_id' => $color->id , 'quantity' => $new_stock]);
                $add = $add+$size->quantity;
            }
            $product = Products::where('id',$product->id)->first();
            $product->stock = $product->initial_stock + $add;
            $product->save();
        }
        return $add;
    }

    public function updateAdmin(Request $request, $id){
        try{

            $admin = Admins::find($id);
            $admin->name = $request->name;
            $admin->email = $request->email;
            $admin->role = json_encode($request->role);
            $admin->save();
            return response('updated',201);
        }catch (Exception $e){
            return response('error',500);
        }
    }

    public function deleteAdmin(Request $request){
        try{
        $admin = Admins::where('id',$request->id)->first();
        $admin->delete();
        return response('deleted',201);
    }catch (Exception $e){
        return response('error',500);
}
    }










    public function setSale($input,$user){
        $sale = new Sales();
        $sale->reference = $input->reference;
        $sale->buyer_id = $input->buyerId;
        $sale->warehouse_id = $user->id ;
        $sale->history = json_encode($input);
        $sale->status = "0";
        $sale->save();
        $sale = Sales::orderBy('id','DESC')->first();
        return $sale->id;
    }

    public function changeAccountStatusOfProduct($products){
        foreach ($products as $item){
            $product = Products::find($item->id);
            $product->purchase_unit_price = $item->price;
            $product->save();
        }
    }

    public function getTotalPurchasedValue($product){
        $total = floatval($product->stock)*floatval($product->purchase_unit_price);
        return $total;
    }

    protected function setPurchasePriceOfProduct($products,$sale){
        foreach ($products as $item){
            $product = Products::find($item->id);
            $product->sale()->updateExistingPivot($sale,['price' => $item->price]);
        }

    }

    public function storeNewLedger(Request $request){
        try{
            $category = Ledger_category::find($request->ledger_category);
            $ledger = new Ledgers();
            $ledger->name = trim($request->name);
            $ledger->other = trim($request->other);
            $ledger->opening_balance = $request->opening_balance;
            $ledger->opening_balance_type = trim($request->opening_balance_type);
            $category->ledger()->save($ledger);
            $response = [
                'message' => 'created'
            ];
            return response(json_encode($response,201));
        }
        catch (Exception $e){
            error_reporting($e);
        }

    }

    public function storeLedgerCategories(Request $request){
        $cat = new Ledger_categorie();
        $cat->name = $request->name;
        $cat->save();
        $group = Ledger_categorie::orderBy('id','DESC')->first();
        return response($group,201);

    }

    public function storeSoldProduct(Request $request){
        $user = User::where('api_token',$request->header('api-token'))->first();
        try{
            $input = json_decode($request->sale);
            $id = $this->setSale($input,$user);
            $sale = Sales::where('id',$id)->first();
            $prd = $input->products;
            foreach ($prd as $item){
                $product = Products::where('id',$item->id)->first();
                $amount_to_reduce = $this->updateProductStock($product,$item->colors);
                $product->stock = $amount_to_reduce;
                $product->sale()->save($sale,['total_amount' => $amount_to_reduce]);
            }

            $response = [
                'message' => 'created'
            ];
            return response(json_encode($response,201));
        }catch (Exception $e){
            return response("Internal Server Error",500);
        }

    }


}
