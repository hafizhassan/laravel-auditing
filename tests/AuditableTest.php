<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2017
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace OwenIt\Auditing\Tests;

use Illuminate\Support\Facades\Config;
use Mockery;
use Orchestra\Testbench\TestCase;
use OwenIt\Auditing\Tests\Stubs\AuditableModelStub;
use RuntimeException;

class AuditableTest extends TestCase
{
    /**
     * Test the toAudit() method to FAIL (Invalid audit event).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage A valid audit event must be set
     *
     * @return void
     */
    public function testToAuditFailInvalidAuditEvent()
    {
        $model = new AuditableModelStub();

        // Invalid auditable event
        $model->setAuditEvent('foo');

        $model->toAudit();
    }

    /**
     * Test the toAudit() method to FAIL (Audit event method missing).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Unable to handle "foo" event, auditFooAttributes() method missing
     *
     * @return void
     */
    public function testToAuditFailAuditEventMethodMissing()
    {
        $model = Mockery::mock(AuditableModelStub::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $model->shouldReceive('isEventAuditable')
            ->andReturn(true);

        $model->setAuditEvent('foo');

        $model->toAudit();
    }

    /**
     * Test the toAudit() method to FAIL (Invalid User id resolver).
     *
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Invalid User resolver type, callable expected
     *
     * @return void
     */
    public function testToAuditFailInvalidUserIdResolver()
    {
        Config::set('audit.user.resolver', null);

        $model = new AuditableModelStub();

        $model->setAuditEvent('created');

        $model->toAudit();
    }

    /**
     * Test the toAudit() method to PASS.
     *
     * @return void
     */
    public function testToAuditPass()
    {
        Config::set('audit.user.resolver', function () {
            return rand(1, 256);
        });

        $model = new AuditableModelStub();

        $model->setAuditEvent('created');
        $auditData = $model->toAudit();

        $this->assertArrayHasKey('old_values', $auditData);
        $this->assertArrayHasKey('new_values', $auditData);
        $this->assertArrayHasKey('event', $auditData);
        $this->assertArrayHasKey('auditable_id', $auditData);
        $this->assertArrayHasKey('auditable_type', $auditData);
        $this->assertArrayHasKey('user_id', $auditData);
        $this->assertArrayHasKey('url', $auditData);
        $this->assertArrayHasKey('ip_address', $auditData);
        $this->assertArrayHasKey('created_at', $auditData);
    }

    /**
     * Test the getAuditableEvents() method to PASS (default values).
     *
     * @return void
     */
    public function testGetAuditableEventsPassDefault()
    {
        $model = new AuditableModelStub();

        $events = $model->getAuditableEvents();

        $this->assertCount(4, $events);
    }

    /**
     * Test the getAuditableEvents() method to PASS (custom values).
     *
     * @return void
     */
    public function testGetAuditableEventsPassCustom()
    {
        $model = new AuditableModelStub();

        $model->auditableEvents = [
            'created',
        ];

        $events = $model->getAuditableEvents();

        $this->assertCount(1, $events);
    }

    /**
     * Test the transformAudit() method to PASS.
     *
     * @return void
     */
    public function testTransformAuditPass()
    {
        $model = new AuditableModelStub();

        $data = $model->transformAudit([]);

        $this->assertEquals([], $data);
    }

    /**
     * Test the getAuditDriver() method to PASS (default).
     *
     * @return void
     */
    public function testGetAuditDriverDefaultPass()
    {
        $model = new AuditableModelStub();

        $this->assertNull($model->getAuditDriver());
    }

    /**
     * Test the getAuditDriver() method to PASS (custom).
     *
     * @return void
     */
    public function testGetAuditDriverCustomPass()
    {
        $model = new AuditableModelStub();

        $model->setAuditDriver('database');

        $this->assertEquals('database', $model->getAuditDriver());
    }

    /**
     * Test the getAuditThreshold() method to PASS (default).
     *
     * @return void
     */
    public function testGetAuditThresholdDefaultPass()
    {
        $model = new AuditableModelStub();

        $this->assertEquals(0, $model->getAuditThreshold());
    }

    /**
     * Test the getAuditThreshold() method to PASS (custom).
     *
     * @return void
     */
    public function testGetAuditThresholdCustomPass()
    {
        $model = new AuditableModelStub();

        $model->setAuditThreshold(100);

        $this->assertEquals(100, $model->getAuditThreshold());
    }
}
