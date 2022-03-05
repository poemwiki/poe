<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\NFT;
use App\Models\Poem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Class LanguageController.
 */
class NFTAPIController extends Controller {
    public function __construct() {
    }

    public function listing(Request $request) {
        $poemID = $request->input('id');
        $price  = $request->input('price');

        $poem = Poem::findOrFail($poemID);
        if ($poem->nft) {
            $nft = $poem->nft;
        } else {
            try {
                $nft = $this->mint($poem, $request->user()->id);
            } catch (Throwable $e) {
                Log::error($e->getMessage());

                return $this->responseFail([], 'Failed to mint NFT');
            }
        }

        $this->addListing($nft, $price);

        return $this->responseSuccess(['id' => $nft->id]);
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
     * @return NFT|\Illuminate\Database\Eloquent\Model
     */
    protected function mint($poem, $userID) {
        // TODO check if poem is already minted to NFT
        // check is original
        if ($poem->isTranslated) {
            throw new \Exception('Can not mint a translated poem currently.');
        }
        // check ownership of poem
        if ($poem->isOwned) {
            throw new \Exception('Can not mint this poem.');
        }
        if ($poem->owner->id !== $userID) {
            throw new \Exception('Can not mint a poem that is not owned by you.');
        }

        \DB::beginTransaction();

        try {
            $nft = NFT::create([
                'poem_id'      => $poem->id,
                'content_id'   => $poem->content_id,
                'type'         => NFT::TYPE['ERC721'],
                'name'         => $poem->title,
                'external_url' => $poem->url,
                // todo: poet and translator page url
                'poemwiki' => json_encode($poem->only(['title', 'subtitle', 'preface', 'poem', 'year', 'month', 'date', 'location', 'poet', 'poet'])),
                'hash'     => Str::digest([
                    'author_user_id' => $poem['user_id'],
                    'title'          => $poem['title'],
                    'subtitle'       => $poem['subtitle'],
                    'preface'        => $poem['preface'],
                    'content'        => $poem['poem'],
                    'poet'           => $poem['poet'],
                ])
            ]);
            Transaction::create([
                'nft_id'       => $nft->id,
                'from_user_id' => 0,
                'to_user_id'   => $userID,
                'amount'       => 1,
                'action'       => Transaction::ACTION['mint'],
            ]);
            \DB::commit();
        } catch (Throwable $e) {
            \DB::rollback();

            throw new \Exception('Minting error occurred.');
        }

        return $nft;
    }
}