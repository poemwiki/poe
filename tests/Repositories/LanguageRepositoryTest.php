<?php namespace Tests\Repositories;

use App\Models\Language;
use App\Repositories\LanguageRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\ApiTestTrait;

class LanguageRepositoryTest extends TestCase
{
    use ApiTestTrait, DatabaseTransactions;

    /**
     * @var LanguageRepository
     */
    protected $languageRepo;

    public function setUp() : void
    {
        parent::setUp();
        $this->languageRepo = \App::make(LanguageRepository::class);
    }

    /**
     * @test create
     */
    public function test_create_language()
    {
        $language = factory(Language::class)->make()->toArray();

        $createdLanguage = $this->languageRepo->create($language);

        $createdLanguage = $createdLanguage->toArray();
        $this->assertArrayHasKey('id', $createdLanguage);
        $this->assertNotNull($createdLanguage['id'], 'Created Language must have id specified');
        $this->assertNotNull(Language::find($createdLanguage['id']), 'Language with given id must be in DB');
        $this->assertModelData($language, $createdLanguage);
    }

    /**
     * @test read
     */
    public function test_read_language()
    {
        $language = factory(Language::class)->create();

        $dbLanguage = $this->languageRepo->find($language->id);

        $dbLanguage = $dbLanguage->toArray();
        $this->assertModelData($language->toArray(), $dbLanguage);
    }

    /**
     * @test update
     */
    public function test_update_language()
    {
        $language = factory(Language::class)->create();
        $fakeLanguage = factory(Language::class)->make()->toArray();

        $updatedLanguage = $this->languageRepo->update($fakeLanguage, $language->id);

        $this->assertModelData($fakeLanguage, $updatedLanguage->toArray());
        $dbLanguage = $this->languageRepo->find($language->id);
        $this->assertModelData($fakeLanguage, $dbLanguage->toArray());
    }

    /**
     * @test delete
     */
    public function test_delete_language()
    {
        $language = factory(Language::class)->create();

        $resp = $this->languageRepo->delete($language->id);

        $this->assertTrue($resp);
        $this->assertNull(Language::find($language->id), 'Language should not exist in DB');
    }
}
