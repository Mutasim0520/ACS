<?php

namespace App\Http\Controllers;
use App\Journal;
use App\Ledger_categorie;
use App\User as User;
use DeepCopy\f008\B;
use Faker\Provider\File;
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
use Mockery\CountValidator\Exception;
use App\Ledger as Ledgers;
use App\Journal as Journals;
use App\Ledger_categorie as Ledger_category;
use App\Sales_historie as Sale_history;
use App\Accounts_sale_historie as Accounts_sale_history;
use App\Accounts_purchase_historie as Accounts_purchase_history;
use App\Product_image as image;


class PostController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth',['except' => ['getProducts']]);
    }

    public function duplicateChecker($table,$column,$value){
        $match = DB::table($table)->where($column, $value)->first();
        if($match){
            return "red";
        }
        else return "green";
    }

    public function storeSuppliers(Request $request){
        $this->validate($request,[
            'company' => 'required|max:255'
        ]);

        $supplier = ucwords(trim($request->supplier));
        $company = ucwords(trim($request->company));
        $signal = $this->duplicateChecker('suppliers','company',$company);
        if($signal == 'red'){
            $response = [
                "status" => "already exist"
            ];
            return response(json_encode($response),200);
        }
        else{
            $newSupplier = new Suppliers();
            $newSupplier->company = $company;
            $newSupplier->mobile = trim($request->mobile);
            $newSupplier->address = trim($request->address);
            $newSupplier->save();
            $supplier = Suppliers::orderBy('id','DESC')->first();
            $response = [
                "status" => "New Item Created",
                "supplier" =>$supplier
            ];
            return response(json_encode($response),201);
        }
    }

    public function storeCategories(Request $request){
        $this->validate($request,[
            'category' => 'required|max:255'
        ]);
        $category = ucwords(trim($request->category));
        $signal = $this->duplicateChecker('categories','name',$category);
        if($signal == 'red'){
            $response = [
                "status" => "already exist"
            ];
            return response(json_encode($response),200);
        }
        else{
            $newCategory = new Category();
            $newCategory->name = $category;
            $newCategory->save();
            $category = Category::orderBy('id','DESC')->first();
            $response = [
                "status" => "New Item Created",
                "category" =>$category
            ];
            return response(json_encode($response),201);
        }
    }

    public function storeSubCategories(Request $request){
        $this->validate($request,[
            'category_id' => 'required|max:255',
            'subcategory' =>'required|max:255'
        ]);
        $subcategory = ucwords(trim($request->subcategory));
        $signal = $this->duplicateChecker('sub_categories','name',$subcategory);
        if($signal == 'red'){
            $response = [
                "status" => "already exist"
            ];
            return response(json_encode($response),201);
        }
        else{
            $newSubCategory = new SubCategory();
            $newSubCategory->name = $subcategory;
            $newSubCategory->save();
            $subcategory = SubCategory::orderBy('id','DESC')->first();
            $category = Category::find($request->category_id);
            $subcategory->category()->save($category);
            $response = [
                "status" => "New Item Created",
                "subcategory" =>$subcategory
            ];
            return response(json_encode($response),200);
        }
    }

    public function storeColors(Request $request){
        $this->validate($request,[
            'color' => 'required|max:255',
            'hex' => 'required|max:255'
        ]);
        $color = ucwords(trim($request->color));
        $hex = trim($request->hex);
        $signal = $this->duplicateChecker('colors','name',$color);
        if($signal == 'red'){
            $response = [
                "status" => "already exist"
            ];
            return response(json_encode($response),201);
        }
        else{
            $newColor = new Colors();
            $newColor->name = $color;
            $newColor->hex = $hex;
            $newColor->save();
            $color = Colors::orderBy('id','DESC')->first();
            $response = [
                "status" => "New Item Created",
                "color" =>$color
            ];
            return response(json_encode($response),201);
        }
    }

    public function storeSizes(Request $request){
        $this->validate($request,[
            'size' => 'required|max:255'
        ]);
        $size = strtoupper(trim($request->size));
        $signal = $this->duplicateChecker('sizes','name',$size);
        if($signal == 'red'){
            $response = [
                "status" => "already exist"
            ];
            return response(json_encode($response),200);
        }
        else{
            $newSize = new Sizes();
            $newSize->name = $size;
            $newSize->save();
            $size = Sizes::orderBy('id','DESC')->first();
            $response = [
                "status" => "New Item Created",
                "size" =>$size
            ];
            return response(json_encode($response),201);
        }
    }

    public function storeBuyers(Request $request){
        $this->validate($request,[
            'company' => 'required|max:255'
        ]);
        $company = ucwords(trim($request->company));
        $signal= $this->duplicateChecker('buyers','company',$company);
        if($signal == 'red'){
            $response = [
                "status" => "already exist"
            ];
            return response(json_encode($response),201);
        }
        else{
            $newBuyer = new Buyers();
            $newBuyer->company = $company;
            $newBuyer->save();
            $supplier = Suppliers::orderBy('id','DESC')->first();
            $response = [
                "status" => "New Item Created",
                "supplier" =>$supplier
            ];
            return response(json_encode($response),201);
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

    public function setPurchase($input,$user){
        $purchase = new Purchases();
        $purchase->reference = $input->reference;
        $purchase->supplier_id = $input->supplierId;
        $purchase->warehouse_id = $user->id ;
        $purchase->save();
        $purchase = Purchases::orderBy('id','DESC')->first();

        $history = new History();
        $history->history = json_encode($input);
        $history->purchase_id = $purchase->id;
        $history->save();

        return $purchase->id;
    }

    public function setSale($input,$user){
        $sale = new Sales();
        $sale->reference = $input->reference;
        $sale->buyer_id = $input->buyerId;
        $sale->warehouse_id = $user->id ;
        $sale->status = "0";
        $sale->save();
        $sale = Sales::orderBy('id','DESC')->first();

        $history = new Sale_history();
        $history->history = json_encode($input);
        $sale->sales_historie()->save($history);
        return $sale->id;
    }

    public function setAmounts($product,$items){
        foreach ($items as $color){
            foreach ($color->sizes as $size){
                $new = Sizes::where('id',$size->id)->first();
                $new->product()->save($product,['color_id' => $color->id , 'quantity' => $size->quantity]);
            }
        }
    }

    public function storePurchasedProduct(Request $request){
        $user = User::where('api_token',$request->header('api-token'))->first();
        try{
            $input = json_decode($request->purchase);
            $purchase = $this->setPurchase($input,$user);
            $prd = $input->products;
            $supplier = Suppliers::where('id',$input->supplierId)->first();
            foreach ($prd as $item){
                $category = Category::where('id',$item->category)->first();
                $sub_category = SubCategory::where('id',$item->sub_category)->first();
                $meta = $category->name.'_'.$sub_category->name.'_'.$item->name.'_'.$supplier->name.'_'.$input->reference;
                $product = new Products();
                $product->name = $item->name;
                $product->meta = $meta;
                $product->categorie_id = $item->category;
                $product->sub_categorie_id = $item->sub_category;
                $product->purchase_id = $purchase;
                $stock = $this->countTotal($item->colors);
                $product->stock = $stock;
                $product->initial_stock = $stock;
                $product->save();
                $product = Products::orderBy('id','DESC')->first();
                $this->setAmounts($product,$item->colors);
            }

            $response = [
                'message' => 'created'
            ];
            return response(json_encode($response,201));
        }catch (Exception $e){
            return response("Internal Server Error",500);
        }
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

    public function changeAccountStatusOfProduct($products){
        foreach ($products as $item){
            $product = Products::find($item->id);
            $product->purchase_unit_price = $item->price;
            $product->save();
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

    public function getTotalPurchasedValue($product){
        $total = floatval($product->stock)*floatval($product->purchase_unit_price);
        return $total;
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

    public function setPSIntoJournal($item,$user,$action_type,$action){
        $category = $item->payment_category;
        $type = $item->payment_type;
        $discount = $action->discount;
        if($action_type == 'purchase'){
            $supplier = $action->supplier;
            $amount = intval($item->total)-intval($discount);
            $narration = "Purchased goods from $supplier->company of BDT $amount and got discount of BDT $discount";

            $journal = new Journals();
            $journal->user_id = $user->id;
            $journal->purchase_id = $action->id;
            $journal->account = 'purchase';
            $journal->save();
            $journal = Journals::orderBy('id','DESC')->first();

            $narration_2 ="";
            $this->setIntoJournal('Purchase','Dr',$item->total,$journal);
            if($discount){
                $this->setIntoJournal($supplier->company,'Cr',$amount,$journal);
                $this->setIntoJournal('Discount','Cr',$discount,$journal);
            }
            else $this->setIntoJournal($supplier->company,'Cr',$item->total,$journal);

            $journal_2 = new Journals();
            $journal_2->user_id = $user->id;
            $journal_2->purchase_id = $action->id;
            $journal_2->account = 'purchase';
            $journal_2->save();
            $journal_2 = Journals::orderBy('id','DESC')->first();

            if($category == '3'){
                $value = $item->partial;
                $check = $item->check;
                if($type == '3'){
                    $total = intval($value->cash)+intval($value->check);
                    $narration_2 = "Paid $supplier->company BDT $value->cash in cash and BDT $value->check in check at $check.";
                    $this->setIntoJournal($supplier->company,'Dr',$total,$journal_2);
                    $this->setIntoJournal($check,'Cr',$value->check,$journal_2);
                    $this->setIntoJournal('Cash','Cr',$value->cash,$journal_2);
                }

                elseif ($type == '2'){
                    $narration_2 = "Paid $supplier->company BDT $value->check in check at $check.";
                    $this->setIntoJournal($supplier->company,'Dr',intval($value->check),$journal_2);
                    $this->setIntoJournal($check,'Cr',$value->check,$journal_2);
                }

                elseif ($type == '1'){
                    $narration_2 = "Paid $supplier->company BDT $value->cash in cash.";
                    $this->setIntoJournal($supplier->company,'Dr',intval($value->cash),$journal_2);
                    $this->setIntoJournal('Cash','Cr',$value->cash,$journal_2);
                }
            }

            elseif ($category == '1'){
                $amount = intval($item->total)-intval($discount);
                $check = $item->check;
                if ($type == '2'){
                    $narration_2 = "Paid $supplier->company BDT $amount in check at $check.";
                    $this->setIntoJournal($supplier->company,'Dr',$amount,$journal_2);
                    $this->setIntoJournal($check,'Cr',$amount,$journal_2);
                }
                elseif ($type == '1'){
                    $narration_2 = "Paid $supplier->company BDT $amount in cash.";
                    $this->setIntoJournal($supplier->company,'Dr',$amount,$journal_2);
                    $this->setIntoJournal('Cash','Cr',$amount,$journal_2);
                }
                elseif($type == '3'){
                    $value = $item->partial;
                    $narration_2 = "Paid $supplier->company BDT $value->cash in cash and BDT $value->check in check at $check.";
                    $this->setIntoJournal($supplier->company,'Dr',$amount,$journal_2);
                    $this->setIntoJournal('Cash','Cr',$value->cash,$journal);
                    $this->setIntoJournal($check,'Cr',$value->check,$journal);
                }
            }

            $journal->narration = $narration;
            $journal->save();

            $journal_2->narration = $narration_2;
            $journal_2->save();

        }
        else{
            $buyer = $action->buyer;
            $amount = intval($item->total)-intval($discount);
            $narration = "Sold goods to $buyer->company of BDT $amount and got discount of BDT $discount";

            $journal = new Journals();
            $journal->user_id = $user->id;
            $journal->sale_id = $action->id;
            $journal->account = 'sale';
            $journal->save();
            $journal = Journals::orderBy('id','DESC')->first();

            $narration_2 ="";
            $this->setIntoJournal('Sale','Cr',$item->total,$journal);
            if($discount){
                $this->setIntoJournal($buyer->company,'Dr',$amount,$journal);
                $this->setIntoJournal('Discount','Dr',$discount,$journal);
            }
            else $this->setIntoJournal($buyer->company,'Dr',$item->total,$journal);

            $journal_2 = new Journals();
            $journal_2->user_id = $user->id;
            $journal_2->sale_id = $action->id;
            $journal->account = 'sale';
            $journal_2->save();
            $journal_2 = Journals::orderBy('id','DESC')->first();

            if($category == '3'){
                $value = $item->partial;
                $check = $item->check;
                if($type == '3'){
                    $total = intval($value->cash)+intval($value->check);
                    $narration_2 = "Recieved from $buyer->company BDT $value->cash in cash and BDT $value->check in check at $check.";
                    $this->setIntoJournal($buyer->company,'Cr',$total,$journal_2);
                    $this->setIntoJournal($check,'Dr',$value->check,$journal_2);
                    $this->setIntoJournal('Cash','Dr',$value->cash,$journal_2);
                }

                elseif ($type == '2'){
                    $narration_2 = "Recieved from $buyer->company BDT $value->check in check at $check.";
                    $this->setIntoJournal($buyer->company,'Cr',intval($value->check),$journal_2);
                    $this->setIntoJournal($check,'Dr',$value->check,$journal_2);
                }

                elseif ($type == '1'){
                    $narration_2 = "Recieved from $buyer->company BDT $value->cash in cash.";
                    $this->setIntoJournal($buyer->company,'Cr',intval($value->cash),$journal_2);
                    $this->setIntoJournal('Cash','Dr',$value->cash,$journal_2);
                }
            }

            elseif ($category == '1'){
                $amount = intval($item->total)-intval($discount);
                $check = $item->check;
                if ($type == '2'){
                    $narration_2 = "Recieved from $buyer->company BDT $amount in check at $check.";
                    $this->setIntoJournal($buyer->company,'Cr',$amount,$journal_2);
                    $this->setIntoJournal($check,'Dr',$amount,$journal_2);
                }
                elseif ($type == '1'){
                    $narration_2 = "Recieved from $buyer->company BDT $amount in cash.";
                    $this->setIntoJournal($buyer->company,'Cr',$amount,$journal_2);
                    $this->setIntoJournal('Cash','Dr',$amount,$journal_2);
                }
                elseif($type == '3'){
                    $value = $item->partial;
                    $narration_2 = "Recieved from $buyer->company BDT $value->cash in cash and BDT $value->check in check at $check.";
                    $this->setIntoJournal($buyer->company,'Cr',$amount,$journal_2);
                    $this->setIntoJournal('Cash','Dr',$value->cash,$journal);
                    $this->setIntoJournal($check,'Dr',$value->check,$journal);
                }
            }

            $journal->narration = $narration;
            $journal->save();

            $journal_2->narration = $narration_2;
            $journal_2->save();
        }
    }

    public function storePurchasedProductPrice(Request $request){
        $user = User::where('api_token',$request->header('api-token'))->first();
        try{
            $input = json_decode($request->purchase);
            $purchase = Purchases::find($input->purchaseId);
            if($purchase->status == 'complete'){
                $response = [
                    'message' => 'already completed'
                ];
                return response(json_encode($response,304));
            }
            else{
                $purchase->transport = $input->transport;
                $purchase->vat = $input->vat;
                $purchase->discount = $input->discount;
                $purchase->labour = $input->labour;
                $purchase->other = $input->others;
                $purchase->accounts_id = $user->id;
                $purchase->status = "complete";
                $flag = "paid";
                $stock_value = 0;
                $due = 0;
                foreach ($input->products as $item){
                    $stock_value = $stock_value+$item->total;
                    if($item->payment_category ==  2 || $item->payment_category == 3){
                        $flag = "due";
                        if($item->payment_category ==  2){
                            $due = $due+$item->total;
                        }
                        else if($item->payment_category ==  3){
                            $payment = $item->partial;
                            if($item->payment_type == 1){
                                $due = $due+($item->total-$payment->cash);
                            }
                            else if($item->payment_type == 2){
                                $due = $due+($item->total-$payment->check);
                            }
                            else if($item->payment_type == 3){
                                $due = $due+($item->total-$payment->check-$payment->cash);
                            }
                        }
                    }
                }
                if($input->discount){
                    $due = $due-$input->discount;
                }
                $purchase->payment_status = $flag;
                $purchase->total_value = $stock_value;
                $purchase->due = $due;
                $purchase->save();

                $hist = new Accounts_purchase_history();
                $hist->history = json_encode($input);
                $hist->purchase_id = $input->purchaseId;
                $hist->save();


                $purchase = Purchases::with('supplier','product')->where('id',$input->purchaseId)->first();

                $this->createNewLedger($purchase->supplier);
                $total = 0;
                foreach ($input->products as $item){
                    $this->setPSIntoJournal($item , $user , 'purchase' , $purchase);
                    $total = floatval($item->total)+$total;
                }
                $supplier = $purchase->supplier;

                if($purchase->transport){
                    $narration = "Transport cost of BDT ".$purchase->transport;
                    $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'transport');
                    $this->setIntoJournal('Transport','Dr',$purchase->transport,$journal);
                    $this->setIntoJournal('Cash','Cr',$purchase->transport,$journal);

                    $narration = "Transport cost of BDT ".$purchase->transport." for purchasing product from $supplier->company";
                    $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'transport');
                    $this->setIntoJournal('Transport','Dr',$purchase->transport,$journal);
                    $this->setIntoJournal($supplier->company,'Cr',$purchase->transport,$journal);
                }
                if($purchase->labour){
                    $narration = "Labour cost of BDT ".$purchase->labour;
                    $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'labour');
                    $this->setIntoJournal('Labour','Dr',$purchase->labour,$journal);
                    $this->setIntoJournal('Cash','Cr',$purchase->labour,$journal);

                    $narration = "Labour cost of BDT ".$purchase->labour." for purchasing product from $supplier->company";
                    $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId, 'labour');
                    $this->setIntoJournal('Labour','Dr',$purchase->labour,$journal);
                    $this->setIntoJournal($supplier->company,'Cr',$purchase->labour,$journal);
                }
                if($purchase->other){
                    $narration = "Others cost of BDT ".$purchase->other;
                    $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId, 'others');
                    $this->setIntoJournal('Other','Dr',$purchase->other,$journal);
                    $this->setIntoJournal('Cash','Cr',$purchase->other,$journal);

                    $narration = "Others cost of BDT ".$purchase->other." for purchasing product from $supplier->company";
                    $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'others');
                    $this->setIntoJournal('Other','Dr',$purchase->other,$journal);
                    $this->setIntoJournal($supplier->company,'Cr',$purchase->other,$journal);
                }
//                if($purchase->vat){
//                    $vat = floor($total*13/100);
//                    $narration = "Vat of BDT ".$vat;
//                    $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'vat');
//                    $this->setIntoJournal('Vat','Dr',$vat,$journal);
//                    $this->setIntoJournal($supplier->company,'Cr',$vat,$journal);
//
//                    $narration = "Vat of BDT ".$purchase->transport." for purchasing product from $supplier->company";
//                    $journal = $this->createJournalEntry($narration,$user->id,$input->purchaseId,'vat');
//                    $this->setIntoJournal('Vat','Dr',$vat,$journal);
//                    $this->setIntoJournal('Cash','Cr',$purchase->transport,$journal);
//                }
                $this->changeAccountStatusOfProduct($input->products);

                $response = [
                    'message' => 'created'
                ];
                return response(json_encode($response,201));
            }
        }catch (Exception $e){
           error_reporting($e);
        }
    }

    public function storeSoldProductPrice(Request $request){
        $user = User::where('api_token',$request->header('api-token'))->first();
        try{
            $input = json_decode($request->sale);
            $sale = Sales::find($input->saleId);
            if($sale->status == 'complete'){
                $response = [
                    'message' => 'already completed'
                ];
                return response(json_encode($response,304));
            }
            else{
                $sale->accounts_id = $user->id;
                $sale->status = "complete";
                $flag = "paid";
                $stock_value = 0;
                $due = 0;
                foreach ($input->products as $item){
                    $stock_value = $stock_value+$item->total;
                    if($item->payment_category ==  2 || $item->payment_category == 3){
                        $flag = "due";
                        if($item->payment_category ==  2){
                            $due = $due+$item->total;
                        }
                        else if($item->payment_category ==  3){
                            $payment = $item->partial;
                            if($item->payment_type == 1){
                                $due = $due+($item->total-$payment->cash);
                            }
                            else if($item->payment_type == 2){
                                $due = $due+($item->total-$payment->check);
                            }
                            else if($item->payment_type == 3){
                                $due = $due+($item->total-$payment->check-$payment->cash);
                            }
                        }
                    }
                }
                if($input->discount){
                    $due = $due-$input->discount;
                }
                $sale->payment_status = $flag;
                $sale->total_value = $stock_value;
                $sale->due = $due;
                $sale->due = $input->discount;
                $sale->save();

                $history = new Accounts_sale_history();
                $history->history = json_encode($input);
                $history->sale_id = $sale->id;
                $history->save();


                $sale = Sales::with('buyer','product')->where('id',$input->saleId)->first();

                $this->createNewLedger($sale->buyer);
                $total = 0;
                foreach ($input->products as $item){
                    $this->setPSIntoJournal($item , $user , 'sale' , $sale);
                    $total = floatval($item->total)+$total;
                }

                $this->setSalePriceOfProduct($input->products,$sale);

                $response = [
                    'message' => 'created'
                ];
                return response(json_encode($response,201));
            }
        }catch (Exception $e){
            error_reporting($e);
        }
    }

    protected function setSalePriceOfProduct($products,$sale){
        foreach ($products as $item){
            $product = Products::find($item->id);

            $sale->product()->updateExistingPivot($product->id,['price' => $item->price]);
        }

    }

    protected function createJournalEntry($narration,$user_id,$purchase_id,$account){
        $journal = new Journals();
        $journal->user_id = $user_id;
        $journal->purchase_id = $purchase_id;
        $journal->narration = $narration;
        $journal->account = $account;
        $journal->save();
        $journal = Journals::orderBy('id','DESC')->first();
        return $journal;
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
        }
        $product = Products::where('id',$product->id)->first();
        $product->stock = $product->initial_stock - $reduce;
        $product->save();
        return $reduce;
    }

    public function storeProductDetails(Request $request){
        try{
            $product = Products::find($request->id);
            $product->detail = $request->detail;
            if($request->hasFile('doc')){
                $fileName = time().'.'.$request->doc->getClientOriginalExtension();
                $request->doc->move('uploads/docs', $fileName);
                $product->file = $fileName;
            }
            $product->save();
            if($request->hasFile('photos')){
                $counter = 1;
                foreach ($request->photos as $item){
                    $imageName = $counter.time().'.'.$item->getClientOriginalExtension();
                    $item->move('uploads/images', $imageName);
                    $photo = new image();
                    $photo->image_path = $imageName;
                    $photo->product_id = $product->id;
                    $photo->save();
                    $counter++;
                }
            }
            return response('updated',201);

        }
        catch (Exception $e){
            return "error";
        }
    }

    public function deleteDoc(Request $request){
        try{
            $product = Products::find($request->id);
            $product->file = '';
            $product->save();
            return response('deleted',201);
        }
        catch (Exception $e){
            return response('error',500);
        }
    }

    public function deletePhoto(Request $request){
        try{
            $photo = image::find($request->id);
            $photo->delete();
            return response('deleted',201);
        }
        catch (Exception $e){
            return response('error',500);
        }
    }


}
