<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\CreateLanguageAPIRequest;
use App\Http\Requests\API\UpdateLanguageAPIRequest;
use App\Models\Language;
use App\Repositories\LanguageRepository;
use Illuminate\Http\Request;
use Response;

/**
 * Class LanguageController
 * @package App\Http\Controllers\API
 */

class LanguageAPIController extends Controller
{
    /** @var  LanguageRepository */
    private $languageRepository;

    public function __construct(LanguageRepository $languageRepo)
    {
        $this->languageRepository = $languageRepo;
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @SWG\Get(
     *      path="/languages",
     *      summary="Get a listing of the Languages.",
     *      tags={"Language"},
     *      description="Get all Languages",
     *      produces={"application/json"},
     *      @SWG\Response(
     *          response=200,
     *          description="successful operation",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  type="array",
     *                  @SWG\Items(ref="#/definitions/Language")
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $languages = $this->languageRepository->all(
            $request->except(['skip', 'limit']),
            $request->get('skip'),
            $request->get('limit')
        );

        return $this->sendResponse($languages->toArray(), 'Languages retrieved successfully');
    }

    /**
     * @param CreateLanguageAPIRequest $request
     * @return Response
     *
     * @SWG\Post(
     *      path="/languages",
     *      summary="Store a newly created Language in storage",
     *      tags={"Language"},
     *      description="Store Language",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Language that should be stored",
     *          required=false,
     *          @SWG\Schema(ref="#/definitions/Language")
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="successful operation",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  ref="#/definitions/Language"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */
    public function store(CreateLanguageAPIRequest $request)
    {
        $input = $request->all();

        $language = $this->languageRepository->create($input);

        return $this->sendResponse($language->toArray(), 'Language saved successfully');
    }

    /**
     * @param int $id
     * @return Response
     *
     * @SWG\Get(
     *      path="/languages/{id}",
     *      summary="Display the specified Language",
     *      tags={"Language"},
     *      description="Get Language",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Language",
     *          type="integer",
     *          required=true,
     *          in="path"
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="successful operation",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  ref="#/definitions/Language"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */
    public function show($id)
    {
        /** @var Language $language */
        $language = $this->languageRepository->find($id);

        if (empty($language)) {
            return $this->sendError('Language not found');
        }

        return $this->sendResponse($language->toArray(), 'Language retrieved successfully');
    }

    /**
     * @param int $id
     * @param UpdateLanguageAPIRequest $request
     * @return Response
     *
     * @SWG\Put(
     *      path="/languages/{id}",
     *      summary="Update the specified Language in storage",
     *      tags={"Language"},
     *      description="Update Language",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Language",
     *          type="integer",
     *          required=true,
     *          in="path"
     *      ),
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Language that should be updated",
     *          required=false,
     *          @SWG\Schema(ref="#/definitions/Language")
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="successful operation",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  ref="#/definitions/Language"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */
    public function update($id, UpdateLanguageAPIRequest $request)
    {
        $input = $request->all();

        /** @var Language $language */
        $language = $this->languageRepository->find($id);

        if (empty($language)) {
            return $this->sendError('Language not found');
        }

        $language = $this->languageRepository->update($input, $id);

        return $this->sendResponse($language->toArray(), 'Language updated successfully');
    }

    /**
     * @param int $id
     * @return Response
     *
     * @SWG\Delete(
     *      path="/languages/{id}",
     *      summary="Remove the specified Language from storage",
     *      tags={"Language"},
     *      description="Delete Language",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Language",
     *          type="integer",
     *          required=true,
     *          in="path"
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="successful operation",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  type="string"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */
    public function destroy($id)
    {
        /** @var Language $language */
        $language = $this->languageRepository->find($id);

        if (empty($language)) {
            return $this->sendError('Language not found');
        }

        $language->delete();

        return $this->sendSuccess('Language deleted successfully');
    }
}
