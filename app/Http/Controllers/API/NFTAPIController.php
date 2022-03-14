<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\NFT;
use App\Models\Poem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Class LanguageController.
 */
class NFTAPIController extends Controller {
    public function __construct() {
    }

    public function listing(Request $request): array {
        $poemID = $request->input('id');
        $price  = $request->input('price');
        $userID = $request->user()->id;

        $poem = Poem::findOrFail($poemID);
        if ($poem->nft) {
            $nft = $poem->nft;
            // check nft owner
            if ($nft->owner && ($nft->owner->id !== $userID)) {
                return $this->responseFail([], 'Can not list an NFT not owned by you.');
            }
        } else {
            try {
                $nft = $this->mint($poem, $userID);
            } catch (Throwable $e) {
                Log::error($e->getMessage());

                return $this->responseFail([], 'Failed to mint NFT.');
            }
        }

        $this->addListing($nft, $price);

        return $this->responseSuccess(['id' => $nft->id]);
    }

    public function unlisting(Request $request): array {
        $nftID  = $request->input('id');
        $userID = $request->user()->id;
        $nft    = NFT::findOrFail($nftID);

        if (!$nft->isUnlistableByUser($userID)) {
            return $this->responseFail([], 'Can not unlisting an NFT not owned by you.');
        }

        try {
            Listing::unlist($nft);
        } catch (\Exception $e) {
            Log::error('unlisting error', $e->getMessage());

            return $this->responseFail([], 'Failed to unlisting NFT.');
        }

        return $this->responseSuccess(['id' => $nftID]);
    }

    public function buy(Request $request): array {
        $nftID  = $request->input('id');
        $userID = $request->user()->id;
        $nft    = NFT::findOrFail($nftID);

        \DB::beginTransaction();

        try {
            $nft->listing->status = Listing::STATUS['sold'];
            $nft->listing->save();

            Transaction::create([
                'nft_id'       => $nft->id,
                'from_user_id' => $nft->balance->user_id,
                'to_user_id'   => $userID,
                'amount'       => 1,
                'action'       => Transaction::ACTION['sell'],
            ]);
            $nft->balance->user_id = $userID;
            $nft->balance->save();

            // gold transfer
            Transaction::create([
                'nft_id'         => 0,
                'from_user_id'   => $userID,
                'to_user_id'     => $nft->balance->user_id,
                'amount'         => $nft->listing->price,
                'action'         => Transaction::ACTION['transfer'],
            ]);
            // TODO gold balance

            \DB::commit();

            return $this->responseSuccess(['id' => $nftID]);
        } catch (\Exception $e) {
            \DB::rollBack();

            Log::error('buy error', $e->getMessage());

            return $this->responseFail([], 'Failed to buy NFT.');
        }
    }

    protected function addListing(NFT $nft, $price) {
        // TODO check if nft is already listed
        return Listing::create([
            'nft_id' => $nft->id,
            'price'  => $price,
            'status' => Listing::STATUS['active'],
        ]);
    }

    /**
     * @param $poem
     * @param $userID
     * @return NFT|\Illuminate\Database\Eloquent\Model
     * @throws \Exception
     */
    protected function mint($poem, $userID) {
        // TODO check if poem is already minted to NFT
        // check is original
        if ($poem->isTranslated) {
            throw new \Exception('Can not mint a translated poem currently.');
        }
        // check ownership of poem
        // if (!$poem->isOwned) {
        //     throw new \Exception('Can not mint this poem.');
        // }
        if ($poem->owner->id !== $userID) {
            throw new \Exception('Can not mint a poem not owned by you.');
        }

        return NFT::mint($poem, $userID);
    }
}