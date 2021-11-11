<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) baserCMS User Community <https://basercms.net/community/>
 *
 * @copyright     Copyright (c) baserCMS User Community
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       http://basercms.net/license/index.html MIT License
 */

namespace BaserCore\Test\TestCase\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Core\Configure;
use Cake\ORM\Marshaller;
use Cake\I18n\FrozenTime;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use BaserCore\Model\Entity\Site;
use BaserCore\Model\Entity\Content;
use BaserCore\TestSuite\BcTestCase;
use BaserCore\Model\Table\ContentsTable;
/**
 * Class ContentTest
 *
 * @package Baser.Test.Case.Model
 * @property ContentsTable $Contents
 */
class ContentsTableTest extends BcTestCase
{

    public $fixtures = [
        'plugin.BaserCore.Users',
        'plugin.BaserCore.UserGroups',
        'plugin.BaserCore.UsersUserGroups',
        'plugin.BaserCore.Sites',
        'plugin.BaserCore.Contents',
        // 'baser.Model.Content.ContentIsMovable',
        'plugin.BaserCore.Model/Content/ContentStatusCheck',
        // 'baser.Routing.Route.BcContentsRoute.SiteBcContentsRoute',
        // 'baser.Routing.Route.BcContentsRoute.ContentBcContentsRoute',
        // 'baser.Default.SiteConfig',
        // 'baser.Default.User',
    ];

    /**
     * Auto Fixtures
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * set up
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->loadFixtures('Contents', 'Sites', 'Users', 'UserGroups', 'UsersUserGroups');
        parent::setUp();
        $config = $this->getTableLocator()->exists('Contents')? [] : ['className' => 'BaserCore\Model\Table\ContentsTable'];
        $this->Contents = $this->getTableLocator()->get('Contents', $config);
    }

    /**
     * Tear Down
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Contents);
        parent::tearDown();
    }

    /**
     * Test initialize
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->assertEquals('contents', $this->Contents->getTable());
        $this->assertTrue($this->Contents->hasBehavior('Tree'));
        $this->assertTrue($this->Contents->hasAssociation('Sites'));
        $this->assertTrue($this->Contents->hasAssociation('Users'));
    }

    /**
     * testGetTrash
     *
     * @return void
     */
    public function testGetTrash(): void
    {
        $result = $this->Contents->getTrash(15);
        $this->assertEquals("BcAdminContentsテスト(deleted)", $result->title);
    }

    /**
     * testDelete
     *
     * @return void
     */
    public function testHardDel(): void
    {
        // treeBehavior falseの場合
        $content1 = $this->Contents->getTrash(15);
        $this->assertTrue($this->Contents->hardDel($content1));
        $this->expectException('Cake\Datasource\Exception\RecordNotFoundException');
        $this->Contents->getTrash(15);
        // treeBehavior trueの場合
        $content2 = $this->Contents->getTrash(16);
        $this->assertTrue($this->Contents->hardDel($content2, true));
        $this->expectException('Cake\Datasource\Exception\RecordNotFoundException');
        $this->Contents->getTrash(16); // 親要素
        $this->expectException('Cake\Datasource\Exception\RecordNotFoundException');
        $this->Contents->getTrash(17); // 子要素
    }

    /**
     * Test validationDefault
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $validator = $this->Contents->validationDefault(new Validator());
        $fields = [];
        foreach($validator->getIterator() as $key => $value) {
            $fields[] = $key;
        }
        $this->assertEquals(['id', 'name', 'title', 'eyecatch', 'self_publish_begin', 'self_publish_end', 'created_date', 'modified_date'], $fields);
    }

    /**
     * testValidationDefaultWithEntity
     *
     * @param  mixed $fields
     * @param  mixed $messages
     * @return void
     * @dataProvider validationDefaultWithEntityDataProvider
     */
    public function testValidationDefaultWithEntity($fields, $messages): void
    {
        $this->loadFixtures('Contents');
        $contents = $this->Contents->newEntity($fields);
        $this->assertSame($messages, $contents->getErrors());
    }
    public function validationDefaultWithEntityDataProvider()
    {
        return [
            [
                [
                    'id' => 'aaa', // 空の場合通る
                    'name' => '',
                    'title' => '',
                ],
                [
                    'id' => ['integer' => "The provided value is invalid"],
                    'name' => ['_empty' => 'スラッグを入力してください。'],
                    'title' => ['_empty' => 'タイトルを入力してください。'],
                ]
            ]
        ];
    }


    /**
     * Implemented Events
     */
    public function testImplementedEvents()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * サイト設定にて、エイリアスを利用してメインサイトと自動連携するオプションを利用時に、
     * 関連するサブサイトで、関連コンテンツを作成する際、同階層に重複名称のコンテンツがないか確認する
     *
     *    - 新規の際は、存在するだけでエラー
     *    - 編集の際は、main_site_content_id が自身のIDでない、alias_id が自身のIDでない場合エラー
     *
     * @dataProvider duplicateRelatedSiteContentDataProvider
     */
    public function testDuplicateRelatedSiteContent($data, $expected)
    {
        $this->markTestIncomplete('こちらのテストはまだ未確認です');
        $this->Content->set(['Content' => $data]);
        $this->assertEquals($expected, $this->Content->duplicateRelatedSiteContent(['name' => $data['name']]));
    }

    public function duplicateRelatedSiteContentDataProvider()
    {
        return [
            [['id' => null, 'name' => 'hoge', 'parent_id' => 5, 'site_id' => 1], true],        // 新規・存在しない
            [['id' => null, 'name' => 'index', 'parent_id' => 1, 'site_id' => 1], false],        // 新規・存在する（alias_id / main_site_content_id あり）
            [['id' => 4, 'name' => 'index', 'parent_id' => 1, 'site_id' => 1], true],        // 既存・存在する（alias_id / main_site_content_id あり）
            [['id' => null, 'name' => null, 'parent_id' => null, 'site_id' => 6], true],    // メインサイトでない場合はエラーとしない
        ];
    }

    /**
     * testBeforeMarshal
     *
     * @param  array $fields
     * @param  array $expected
     * @return void
     * @dataProvider beforeMarshalDataProvider
     */
    public function testBeforeMarshal($fields, $expected)
    {
        $this->loginAdmin($this->getRequest());
        $data = ['content' => $fields];
        $result = $this->Contents->dispatchEvent('Model.beforeMarshal', ['data' => new ArrayObject($data), 'options' => new ArrayObject()]);
        $this->assertNull($result->getResult());
        $content = (array) $result->getData('data')['content'];
        if (isset($fields['title'])) {
            $this->assertEquals($expected['limit'][0], strlen($content['title']));
            $this->assertEquals($expected['limit'][1], strlen($content['name']));
            foreach ($expected['auto'] as $field => $value) {
                if ($field === "created_date") {
                    $this->assertInstanceOf($value, $content[$field]);
                } else {
                    $this->assertEquals($value, $content[$field]);
                }
            }
        }
        if (isset($fields['id'])) {
            $this->assertInstanceOf($expected[0], $content['modified_date']);
        }
    }

    public function beforeMarshalDataProvider()
    {
        return [
            // idがない場合
            [
                ["title" => str_repeat("a", 300), 'parent_id' => '1'],
                [
                    // titleが254文字&&nameが230文字に切られてるか
                    'limit' => [254, 230],
                    // 初期データが挿入されるか
                    'auto' => [
                        "self_status" => false,
                        "self_publish_begin" => null,
                        "created_date" => FrozenTime::class,
                        "site_root" => 0,
                        "author_id" => 1,
                    ]
                ]
            ],
            // idがある場合更新日が入れられてるか
            [
                ["id" => '1'],
                [FrozenTime::class]
            ],
        ];
    }

    /**
     * After Validate
     */
    public function testAfterValidate()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * 一意の name 値を取得する
     *
     * @dataProvider getUniqueNameDataProvider
     */
    public function testGetUniqueName($name, $parent_id, $expected)
    {
        $result = $this->Contents->getUniqueName($name, $parent_id);
        $this->assertEquals($expected, $result);
    }

    public function getUniqueNameDataProvider()
    {
        return [
            ['', 1, ''],
            ['hoge', 1, 'hoge'],
            ['index', 0, 'index'],
            ['index', 1, 'index_2'],
        ];
    }

    /**
     * testAfterMarshal
     *
     * @return void
     */
    public function testAfterMarshal()
    {
        $time = new FrozenTime();
        $data = [
            "name" => "test",
            "created" => $time,
        ];
        $marshall = new Marshaller($this->Contents);

        $this->Contents->getEventManager()->on(
            'Model.afterMarshal',
            function ($event, $entity, $options) {}
        );
        $entity = $marshall->one($data);
        $this->assertEquals($time->i18nFormat('yyyy-MM-dd HH:mm:ss'), $entity->created);
    }

    /**
     * Before Save
     */
    public function testBeforeSave()
    {
        $data = new Entity([
            'id' => 100,
            'parent_id' => 6,
        ]);
        $this->Contents->dispatchEvent('Model.beforeSave', ['entity' => $data, 'options' => new ArrayObject()]);
        $this->assertEquals(6, $this->Contents->beforeSaveParentId);
    }

    /**
     * After Save
     */
    public function testAfterSave()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * 関連するコンテンツ本体のデータキャッシュを削除する
     */
    public function testDeleteAssocCache()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $content = new Content();
        $this->Contents->deleteAssocCache($content);
    }

    /**
     * Before Delete
     */
    public function testBeforeDelete()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * After Delete
     *
     * 関連コンテンツのキャッシュを削除する
     */
    public function testAfterDelete()
    {
        $alias = $this->Contents->find()->where(['alias_id IS NOT' => null])->first();
        $aliased = $this->Contents->get($alias->alias_id);
        $this->Contents->dispatchEvent('Model.afterDelete', [$aliased, new ArrayObject()]);
        // エイリアスが削除されてるか確認
        $this->expectException('Cake\Datasource\Exception\RecordNotFoundException');
        $this->Contents->get($alias->id);
    }

    /**
     * 自データのエイリアスを削除する
     *
     * 全サイトにおけるエイリアスを全て削除
     */
    public function testDeleteAlias()
    {
        $alias = $this->Contents->find()->where(['alias_id IS NOT' => null])->first();
        $aliased = $this->Contents->get($alias->alias_id);
        $this->execPrivateMethod($this->Contents, 'deleteAlias', [$aliased]);
        // エイリアスが削除されてるか確認
        $this->expectException('Cake\Datasource\Exception\RecordNotFoundException');
        $this->Contents->get($alias->id);
    }

    /**
     * メインサイトの場合、連携設定がされている子サイトのエイリアス削除する
     * ※ 自身のエイリアスだった場合削除する
     */
    public function testDeleteRelateSubSiteContentWithAlias()
    {
        $content = $this->Contents->get(6);
        $mockContent = $this->Contents->save(new Content(['site_id' => 6, 'main_site_content_id' => 6, 'alias_id' => 23, 'plugin' => 'BaserCore', 'type' => 'test']));
        $this->execPrivateMethod($this->Contents, 'deleteRelateSubSiteContent', [$content]);
        $this->expectException('Cake\Datasource\Exception\RecordNotFoundException');
        $this->Contents->get($mockContent->id);
    }
    /**
     * メインサイトの場合、連携設定がされている子サイトのエイリアス削除する
     * ※ コンテンツフォルダだった場合子要素をupdateChildrenする
     */
    public function testDeleteRelateSubSiteContentWithChildren()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
        $content = $this->Contents->get(6);
        $mockContent = $this->Contents->save(new Content(['site_id' => 6, 'main_site_content_id' => 6, 'plugin' => 'BaserCore', 'type' => 'ContentFolder']));
        $$this->execPrivateMethod($this->Contents, 'deleteRelateSubSiteContent', [$content]);
    }

    /**
     * メインサイトの場合、連携設定がされている子サイトのエイリアスを追加・更新する
     */
    public function testUpdateRelateSubSiteContent()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * サブサイトのプレフィックスがついていない純粋なURLを取得
     *
     * @dataProvider pureUrlDataProvider
     */
    public function testPureUrl($url, $siteId, $expected)
    {
        $result = $this->Contents->pureUrl($url, $siteId);
        $this->assertEquals($expected, $result);
    }

    public function pureUrlDataProvider()
    {
        return [
            ['', '', '/'],
            ['', '1', '/'],
            ['/m/content', '', '/m/content'],
            // ['/m/content', '1', '/content'], NOTE: siteがエイリアスの場合
        ];
    }

    /**
     * Content data を作成して保存する
     */
    public function testCreateContent()
    {
        $content = ['title' => 'hoge', 'parent_id' => ''];
        $type = 'Contents';
        $result = $this->Contents->createContent($content, 'BaserCore', $type);

        $this->assertEquals($content['title'], $result->title);
        $this->assertEquals($type, $result->type);
    }

    /**
     * testCreateUrl
     *
     * @param int $id コンテンツID
     * @param string $expects 期待するURL
     * @dataProvider createUrlDataProvider
     */
    public function testCreateUrl($id, $expects)
    {
        $this->assertEquals($this->Contents->createUrl($id), $expects);
    }

    public function createUrlDataProvider()
    {
        return [
            ["hogehoge'/@<>1", ''],
            ["", false],
            [1, '/'],
            [4, '/index'],
            [6, '/service/'],
            [10, '/news/'],
            [11, '/service/service1'],
            // NOTE:  4系とのfixtureにおいて違いがあるため注意
            // [2, '/m/'],
            // [3, '/s/'],
            // [4, '/index'],
            // [5, '/service/'],
            // [6, '/m/index'],
            // [7, '/service/contact/'],
            // [9, '/service/service1'],
            // [10, '/s/index'],
            // [11, '/s/news/'],
            // [12, '/s/service/'],
            // [13, '/en/'],
            // [14, '/sub/'],
            // [15, '/another.com/'],
            // [16, '/s/service/contact/'],
            // [17, '/m/news/'],
            // [18, '/en/news/'],
            // [19, '/sub/news/'],
            // [20, '/another.com/news/'],
            // [21, '/en/service/'],
            // [22, '/en/service/service1'],
            // [23, '/sub/service/'],
            // [24, '/sub/service/service1'],
            // [25, '/another.com/service/'],
            // [26, '/m/service/'],
            // [27, '/m/service/contact/'],
            // [28, '/en/service/contact/'],
            // [29, '/sub/service/contact/'],
            // [30, '/another.com/service/contact/'],
            // [31, '/m/service/service1'],
            // [32, '/s/service/service1'],
            // [33, '/another.com/service/service1'],
            // [34, '/en/index'],
            // [35, '/sub/index'],
            // [36, '/another.com/index'],
            // [37, '/another.com/s/'],
            // [38, '/another.com/s/index'],
            // [39, '/another.com/s/news/'],
            // [40, '/another.com/s/service/'],
            // [41, '/another.com/s/service/service1'],
            // [42, '/another.com/s/service/contact/'],
        ];
    }

    /**
     * システムデータを更新する
     *
     * URL / 公開状態 / メインサイトの関連コンテンツID
     */
    public function testUpdateSystemData()
    {
        // idが1以外でnameがない場合はエラー
        $content = new Content(['id' => 100, 'name' => '']);
        $this->assertFalse($this->Contents->updateSystemData($content));
        // self_*を元にstatusなど補完する
        $data = [
            'id' => 100,
            'name' => 'test',
            'status' => null,
            'publish_begin' => null,
            'publish_end' => null,
            'self_status' => true,
            'self_publish_begin' => FrozenTime::now(),
            'self_publish_end' => FrozenTime::now(),
            'parent_id' => 1,
        ];
        $content = new Content($data);
        $this->Contents->updateSystemData($content);
        $content = $this->Contents->get(100);
        $this->assertTrue($content->status);
        $this->assertNotEmpty($content->publish_begin);
        $this->assertNotEmpty($content->publish_end);
        // 親のstatusがfalseになれば、子にも反映
        $parent = $this->Contents->get(1);
        $parent->status = false;
        $this->Contents->save($parent);
        $this->Contents->updateSystemData($content);
        $content = $this->Contents->get(100);
        $this->assertFalse($content->status);
        $this->assertNull($content->publish_begin);
        $this->assertNull($content->publish_end);
        // siteがある場合 未実装
        // $content = $this->Contents->get(100);
        // $content->site = new Site([
        //     'name' => 'testSite',
        //     'main_site_id' => 1,
        // ]);
        // $this->Contents->updateSystemData($content);
    }

    /**
     * 子ノードのURLを全て更新する
     */
    public function testUpdateChildren()
    {
        $this->Contents->updateChildren(18);
        // 孫のurlが更新されてるか確認
        $this->assertEquals("/ツリー階層削除用フォルダー(親)/ツリー階層削除用フォルダー(子)/ツリー階層削除用フォルダー(孫)/", $this->Contents->get(20)->url);
    }

    /**
     * タイプよりコンテンツを取得する
     *
     * @param string $type コンテントのタイプ
     * @param int $entityId
     * @param mixed $expects 期待するコンテントのid (存在しない場合は空配列)
     * @dataProvider findByTypeDataProvider
     */
    public function testFindByType($type, $entityId, $expects)
    {
        $this->loadFixtures('Contents');
        $result = $this->Contents->findByType($type, $entityId);
        if ($result) {
            $result = $result->id;
        }
        $this->assertEquals($expects, $result);
    }

    public function findByTypeDataProvider()
    {
        return [
            ['BcMail.MailContent', null, 9],    // entityId指定なし
            ['BcBlog.BlogContent', 1, 10],    // entityId指定あり
            ['Page', 3, 11],                // プラグイン指定なし
            ['BcBlog.BlogComment', null, null],    // 存在しないタイプ
            [false, null, null]                // 異常系
        ];
    }

    /**
     * タイプよりコンテンツを削除する
     */
    public function testDeleteByType()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * 公開済の conditions を取得
     */
    public function testGetConditionAllowPublish()
    {
        $result = $this->Contents->getConditionAllowPublish();
        $this->assertEquals(true, $result['Contents.status']);
        $this->assertArrayHasKey('Contents.publish_begin <=', $result[0]['or'][0]);
        $this->assertEquals(null, $result[0]['or'][1]['Contents.publish_begin IS']);
        $this->assertEquals('0000-00-00 00:00:00', $result[0]['or'][2]['Contents.publish_begin']);
        $this->assertArrayHasKey('Contents.publish_end >=', $result[1]['or'][0]);
        $this->assertEquals(null, $result[1]['or'][1]['Contents.publish_end IS']);
        $this->assertEquals('0000-00-00 00:00:00', $result[1]['or'][2]['Contents.publish_end']);
    }

    /**
     * 公開されたURLが存在するか確認する
     */
    public function testExistsPublishUrl()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }


    /**
     * データが公開済みかどうかチェックする
     *
     * @dataProvider isPublishDataProvider
     */
    public function testIsPublish($status, $publishBegin, $publishEnd, $expected)
    {
        $result = $this->Contents->isPublish($status, $publishBegin, $publishEnd);
        $this->assertEquals($expected, $result);
    }

    public function isPublishDataProvider()
    {
        return [
            [true, '', '', true],
            [false, '', '', false],
            [true, '0000-00-00 00:00:00', '', true],
            [true, '0000-00-00 00:00:01', '', true],
            [true, date('Y-m-d H:i:s', strtotime("+1 hour")), '', false],
            [true, '', '0000-00-00 00:00:00', true],
            [true, '', '0000-00-00 00:00:01', false],
            [true, '', date('Y-m-d H:i:s', strtotime("+1 hour")), true],
        ];
    }

    /**
     * 移動元のコンテンツと移動先のディレクトリから移動が可能かチェックする
     * @throws Exception
     * @dataProvider isMovableDataProvider
     */
    public function testIsMovable($siteRelated, $currentId, $parentId, $expects)
    {
        $this->markTestIncomplete('こちらのテストはまだ未確認です');
        $this->loadFixtures('ContentIsMovable');
        if (!$siteRelated) {
            $site = $this->Content->Site->find('first', [
                'conditions' => ['id' => 2],
                'recursive' => -1
            ]);
            $site['Site']['relate_main_site'] = false;
            $this->Content->Site->save($site, ['callbacks' => false]);
        }
        $this->assertEquals($expects, $this->Content->isMovable($currentId, $parentId));
    }

    public function isMovableDataProvider()
    {
        return [
            [false, 2, 3, false],    // ファイルを移動、同じファイル名が存在
            [false, 2, 5, false],    // ファイルを移動、同じフォルダ名が存在
            [false, 2, 7, true],    // ファイルを移動、同じ名称が存在しない
            [false, 6, 1, false],    // フォルダを移動、同じファイル名が存在
            [false, 8, 1, false],    // フォルダを移動、同じフォルダ名が存在
            [false, 6, 7, true],    // フォルダを移動、同じ名称が存在しない
            [true, 2, 7, false],    // ファイルを移動、別サイトに同じファイル名が存在
            [true, 6, 7, false],    // フォルダを移動、別サイトに同じファイル名が存在
        ];
    }

    /**
     * タイトル、URL、公開状態が更新されているか確認する
     */
    public function testIsChangedStatus()
    {
        // idが存在しない場合はtrueを返す
        $this->assertTrue($this->Contents->isChangedStatus(100, []));
        // TODO: 存在する場合を書く
    }

    /**
     * オフセットを元にコンテンツを移動する
     * @param  mixed $id
     * @param  mixed $offset
     * @dataProvider moveOffsetDataProvider
     */
    public function testMoveOffset($id, $offset)
    {
        $target = $this->Contents->moveOffset($id, $offset);
        $origin = $this->Contents->get($id + $offset);
        if ($offset > 0) {
            $this->assertGreaterThan($origin->lft, $target->lft);
        } else {
            $this->assertLessThan($origin->lft, $target->lft);
        }
    }

    public function moveOffsetDataProvider()
    {
        return [
            // サービス2でテスト
            [12, 1],
            [12, -1]
        ];
    }


    /**
     * 親のテンプレートを取得する
     */
    public function testGetParentTemplate()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * コンテンツを移動する
     *
     * 基本的に targetId の上に移動する前提となる
     * targetId が空の場合は、同親中、一番下に移動する
     */
    public function testMove()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * 同じ階層における並び順を取得
     *
     * id が空の場合は、一番最後とみなす
     */
    public function testGetOrderSameParent()
    {
        // parent_id=6の全体数
        $this->assertEquals(3, $this->Contents->getOrderSameParent("", "6"));
        // parent_id=6のコンテンツ順序
        $this->assertEquals(1, $this->Contents->getOrderSameParent("11", "6"));
        $this->assertEquals(2, $this->Contents->getOrderSameParent("12", "6"));
        $this->assertEquals(3, $this->Contents->getOrderSameParent("13", "6"));
    }

    /**
     * 関連サイトの関連コンテンツを取得する
     */
    public function testGetRelatedSiteContents()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * キャッシュ時間を取得する
     * @param array viewCacheを利用できるModelデータ
     * @param mixed $cacheTime
     *    - oneHourlater:publish_end が viewDuration より早い場合
     *    - twoHourlater:viewDuration が publish_end より早い場合
     *    - false:上記以外
     * @dataProvider getCacheTimeDataProvider
     */
    public function testGetCacheTime($data, $cacheTime)
    {
        $this->markTestIncomplete('こちらのテストはまだ未確認です');
        // publish_end が viewDuration より早い場合
        if ($cacheTime == 'oneHourlater') {
            Configure::write('BcCache.viewDuration', '+1 hour');
            $result = $this->Content->getCacheTime($data);
            // テスト実行時間により左右されるのでバッファをもって前後5分以内であればgreen
            $later = strtotime('+5 min', strtotime($data['Content']['publish_end'])) - time();
            $ago = strtotime('-5 min', strtotime($data['Content']['publish_end'])) - time();
            $this->assertGreaterThan($result, $later);
            $this->assertLessThan($result, $ago);

            // viewDuration が publish_end より早い場合
        } elseif ($cacheTime == 'twoHourlater') {
            Configure::write('BcCache.viewDuration', '+1 hour');
            $result = $this->Content->getCacheTime($data);
            // テスト実行時間により左右されるのでバッファをもって前後5分以内であればgreen
            $later = strtotime('+5 min', strtotime($data['Content']['publish_end'])) - time();
            $ago = strtotime('-5 min', strtotime($data['Content']['publish_end'])) - time();
            $this->assertGreaterThan($result, $later);
            $this->assertGreaterThan($result, $ago);
        } else {
            $result = $this->Content->getCacheTime($data);
            $this->assertEquals($result, $cacheTime);
        }
    }

    public function getCacheTimeDataProvider()
    {
        return [
            [1, Configure::read('BcCache.viewDuration')],
            [['Content' => []], false],
            [['Content' => ['status' => true, 'publish_end' => date('Y-m-d H:i:s', strtotime('+5 min'))]], 'oneHourlater'],
            [['Content' => ['status' => true, 'publish_end' => date('Y-m-d H:i:s', strtotime('+2 hour'))]], 'twoHourlater'],
        ];
    }


    /**
     * 全てのURLをデータの状況に合わせ更新する
     */
    public function testUpdateAllUrl()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * 指定したコンテンツ配下のコンテンツのURLを一括更新する
     */
    public function testUpdateChildrenUrl()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * コンテンツ管理のツリー構造をリセットする
     */
    public function testResetTree()
    {
        $this->markTestIncomplete('このテストは、まだ実装されていません。');
    }

    /**
     * URLからコンテンツを取得する
     *
     * TODO sameUrl / useSubDomain のテストが書けていない
     * Siteのデータを用意する必要がある
     *
     * @param string $url
     * @param string $publish
     * @param bool $extend
     * @param bool $sameUrl
     * @param bool $useSubDomain
     * @param bool $expected
     * @dataProvider findByUrlDataProvider
     */
    public function testFindByUrl($expected, $url, $publish = true, $extend = false, $sameUrl = false, $useSubDomain = false)
    {
        $this->loadFixtures('Model\Content\ContentStatusCheck', 'Sites');
        $result = (bool)$this->Contents->findByUrl($url, $publish, $extend, $sameUrl, $useSubDomain);
        $this->assertEquals($expected, $result);
    }

    public function findByUrlDataProvider()
    {
        return [
            [true, '/about', true],
            [false, '/service', true],
            [true, '/service', false],
            [false, '/hoge', false],
            [true, '/news/archives/1', true, true],
            [false, '/news/archives/1', true, false],
        ];
    }

}
