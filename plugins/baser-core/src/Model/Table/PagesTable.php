<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       http://basercms.net/license/index.html MIT License
 */

namespace BaserCore\Model\Table;

use ArrayObject;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\Core\Configure;
use Cake\Filesystem\File;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use BaserCore\Utility\BcUtil;
use BaserCore\Annotation\Note;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use BaserCore\Annotation\NoTodo;
use BaserCore\Model\Entity\Page;
use BaserCore\Annotation\Checked;
use BaserCore\Annotation\UnitTest;
use Cake\Datasource\EntityInterface;
use BaserCore\Utility\BcContainerTrait;
use BaserCore\Event\BcEventDispatcherTrait;
use BaserCore\Service\ContentServiceInterface;
use BaserCore\Model\Behavior\BcSearchIndexManagerInterface;

/**
 * Class PagesTable
 */
class PagesTable extends Table implements BcSearchIndexManagerInterface
{
    /**
     * Trait
     */
    use BcEventDispatcherTrait;
    use BcContainerTrait;

    /**
     * 検索テーブルへの保存可否
     *
     * @var boolean
     */
    public $searchIndexSaving = true;

    /**
     * 公開WebページURLリスト
     * キャッシュ用
     *
     * @var mixed
     */
    protected $_publishes = -1;

    /**
     * WebページURLリスト
     * キャッシュ用
     *
     * @var mixed
     */
    protected $_pages = -1;

    /**
     * 最終登録ID
     * モバイルページへのコピー処理でスーパークラスの最終登録IDが上書きされ、
     * コントローラーからは正常なIDが取得できないのでモバイルページへのコピー以外の場合だけ保存する
     *
     * @var int
     */
    private $__pageInsertID = null;

    /**
     * Initialize
     *
     * @param array $config テーブル設定
     * @return void
     * @checked
     * @unitTest
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('BaserCore.BcContents');
        $this->addBehavior('BaserCore.BcSearchIndexManager');
        $this->addBehavior('Timestamp');
        $this->Sites = TableRegistry::getTableLocator()->get('BaserCore.Sites');
    }

    /**
     * Validation Default
     *
     * @param Validator $validator
     * @return Validator
     * @checked
     * @noTodo
     * @unitTest
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
        ->integer('id')
        ->numeric('id', __d('baser', 'IDに不正な値が利用されています。'), 'update')
        ->requirePresence('id', 'update');

        $validator
        ->scalar('contents')
        ->allowEmptyString('contents', null)
        ->maxLengthBytes('contents', 64000, __d('baser', '本稿欄に保存できるデータ量を超えています。'))
        ->add('contents', 'custom', [
            'rule' => [$this, 'phpValidSyntax'],
            'message' => __d('baser', '本稿欄でPHPの構文エラーが発生しました。')
        ])
        ->add('contents', [
            'containsScript' => [
                'rule' => ['containsScript'],
                'provider' => 'bc',
                'message' => __d('baser', '本稿欄でスクリプトの入力は許可されていません。')
            ]
        ]);

        $validator
        ->scalar('draft')
        ->allowEmptyString('draft', null)
        ->maxLengthBytes('draft', 64000, __d('baser', '本稿欄に保存できるデータ量を超えています。'))
        ->add('draft', 'custom', [
            'rule' => [$this, 'phpValidSyntax'],
            'message' => __d('baser', '本稿欄でPHPの構文エラーが発生しました。')
        ])
        ->add('draft', [
            'containsScript' => [
                'rule' => ['containsScript'],
                'provider' => 'bc',
                'message' => __d('baser', '本稿欄でスクリプトの入力は許可されていません。')
            ]
        ]);

        return $validator;
    }

    /**
     * afterSave
     *
     * @param  EventInterface $event
     * @param  EntityInterface $entity
     * @param  ArrayObject $options
     * @return void
     * @checked
     * @noTodo
     * @unitTest
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        // 検索用テーブルに登録
        if ($this->searchIndexSaving) {
            if (empty($entity->content->exclude_search)) {
                $this->saveSearchIndex($this->createSearchIndex($entity));
            } else {
                $this->deleteSearchIndex($entity->id);
            }
        }
    }

    /**
     * 検索用データを生成する
     *
     * @param Page $page
     * @return array|false
     * @checked
     * @unitTest
     * @noTodo
     */
    public function createSearchIndex($page)
    {
        if (!isset($page->id) || !isset($page->content->id)) {
            return false;
        }
        $content = $page->content;
        if (!isset($content->publish_begin)) {
            $content->publish_begin = '';
        }
        if (!isset($content->publish_end)) {
            $content->publish_end = '';
        }

        if (!$content->title) {
            $content->title = Inflector::camelize($content->name);
        }
        $modelId = $page->id;

        $host = '';
        $url = $content->url;
        if (!$content->site) {
            $site = $this->Sites->get($content->site_id);
        } else {
            $site = $content->site;
        }
        if ($site->useSubDomain) {
            $host = $site->alias;
            if ($site->domainType == 1) {
                $host .= '.' . BcUtil::getMainDomain();
            }
            $url = preg_replace('/^\/' . preg_quote($site->alias, '/') . '/', '', $url);
        }
        $detail = $page->contents;
        $description = '';
        if (!empty($content->description)) {
            $description = $content->description;
        }
        return [
            'model_id' => $modelId,
            'type' => __d('baser', 'ページ'),
            'content_id' => $content->id,
            'site_id' => $content->site_id,
            'title' => $content->title,
            'detail' => $description . ' ' . $detail,
            'url' => $url,
            'status' => $content->status,
            'publish_begin' => $content->publish_begin,
            'publish_end' => $content->publish_end
        ];
    }

    /**
     * コントロールソースを取得する
     *
     * @param string $field フィールド名
     * @param array $options
     * @return mixed $controlSource コントロールソース
     */
    public function getControlSource($field, $options = [])
    {
        switch($field) {
            case 'user_id':
            case 'author_id':
                $controlSources[$field] = $this->Content->User->getUserList($options);
                break;
        }

        if (isset($controlSources[$field])) {
            return $controlSources[$field];
        } else {
            return false;
        }
    }

    /**
     * PHP構文チェック
     *
     * @param string $check チェック対象文字列
     * @return bool
     * @checked
     * @unitTest
     * @noTodo
     */
    public function phpValidSyntax($check)
    {
        if (empty($check)) {
            return true;
        }
        if (!Configure::read('BcApp.validSyntaxWithPage')) {
            return true;
        }
        if (!function_exists('exec')) {
            return true;
        }
        // CL版 php がインストールされてない場合はシンタックスチェックできないので true を返す
        exec('php --version 2>&1', $output, $exit);
        if ($exit !== 0) {
            return true;
        }

        if (BcUtil::isWindows()) {
            $tmpName = tempnam(TMP, "syntax");
            $tmp = new File($tmpName);
            $tmp->open("w");
            $tmp->write($check);
            $tmp->close();
            $command = sprintf("php -l %s 2>&1", escapeshellarg($tmpName));
            exec($command, $output, $exit);
            $tmp->delete();
        } else {
            $format = 'echo %s | php -l 2>&1';
            $command = sprintf($format, escapeshellarg($check));
            exec($command, $output, $exit);
        }

        if ($exit === 0) {
            return true;
        }
        $message = __d('baser', 'PHPの構文エラーです') . '： ' . PHP_EOL . implode(' ' . PHP_EOL, $output);
        return $message;
    }
}
