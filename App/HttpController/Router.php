<?php


namespace App\HttpController;


use EasySwoole\Http\AbstractInterface\AbstractRouter;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    function initialize(RouteCollector $routeCollector)
    {
        $routeCollector->addGroup('/api/v1', function (RouteCollector $collector) {

            $apiBasePathOpenV1 = 'Api/V1/';
            //用户模块
            $this->user($collector, $apiBasePathOpenV1);
            //版本模块
            $this->version($collector, $apiBasePathOpenV1);
            //职业模块
            $this->occupation($collector, $apiBasePathOpenV1);
            //天赋模块
            $this->talent($collector, $apiBasePathOpenV1);
            //伤害测试模块
            $this->damage($collector, $apiBasePathOpenV1);
            //wa模块
            $this->wa($collector, $apiBasePathOpenV1);
            //测试
            $this->test($collector, $apiBasePathOpenV1);
            //聊天室
            $this->chatRoom($collector, $apiBasePathOpenV1);
            //帮助中心
            $this->helpCenter($collector, $apiBasePathOpenV1);
            //订单
            $this->order($collector, $apiBasePathOpenV1);
            //微信支付
            $this->wxPay($collector, $apiBasePathOpenV1);
            //微信回调
            $this->wxCallBack($collector, $apiBasePathOpenV1);
            //坐骑相关
            $this->mount($collector, $apiBasePathOpenV1);
            //钱包相关
            $this->wallet($collector, $apiBasePathOpenV1);
            //公共类接口
            $this->common($collector, $apiBasePathOpenV1);
            //宏命令相关接口
            $this->macro($collector, $apiBasePathOpenV1);
        });

    }

    public function talent(RouteCollector $collector, string $basePath = ''){
        //天赋列表
        $collector->get('/talent/get-talent-list',$basePath.'Talent/Talent/getTalentList');
        //天赋技能树
        $collector->post('/talent/get-talent-tree-list',$basePath.'Talent/Talent/getTalentSkillTree');
        //添加用户天赋信息
        $collector->post('/talent/add-user-talent',$basePath.'Talent/Talent/addUserTalent');
        //修改用户天赋信息
        $collector->post('/talent/update-user-talent',$basePath.'Talent/Talent/updateUserTalent');
        //天赋大厅列表
        $collector->post('/talent/get-talent-hall-list',$basePath.'Talent/Talent/getTalentHallList');
        //用户天赋列表
        $collector->post('/talent/get-user-talent-list',$basePath.'Talent/Talent/getUserTalentList');
        //进行天赋大厅的评论
        $collector->post('/talent/create-comment',$basePath.'Talent/Comment/createComment');
        //获取天赋大厅的评论列表
        $collector->get('/talent/get-talent-comment-list',$basePath.'Talent/Comment/getTalentCommentList');
        //删除自己的评论
        $collector->post('/talent/del-comment',$basePath.'Talent/Comment/delComment');
    }

    public function occupation(RouteCollector $collector, string $basePath = '')
    {
        //职业列表
        $collector->get('/occupation/get-occupation-list',$basePath.'Occupation/Occupation/getOccupationList');

    }

    public function version(RouteCollector $collector, string $basePath = '')
    {
        //版本列表
        $collector->get('/version/get-version-list',$basePath.'Version/Version/getVersionList');

    }

    public function user(RouteCollector $collector, string $basePath = '')
    {
        //保存用户信息
        $collector->post('/user',$basePath.'User/Login/saveUserInfo');
        //用户详情
        $collector->get('/user',$basePath.'User/User/getUserInfo');
        //用户收藏列表
        $collector->get('/user/favorites/list',$basePath.'User/User/getFavoritesList');
        //用户添加收藏
        $collector->post('/user/favorites/add',$basePath.'User/User/addFavorites');
        //用户取消收藏
        $collector->post('/user/favorites/cancel',$basePath.'User/User/cancelFavorites');
        //点赞
        $collector->post('/user/likes/add',$basePath.'User/User/addLikes');
        //取消点赞
        $collector->post('/user/likes/cancel',$basePath.'User/User/cancelLikes');
        //点赞和取消点赞
        $collector->post('/user/likes',$basePath.'User/User/toLikes');
        //获取用户点赞、收藏数
        $collector->get('/user/get-num',$basePath.'User/Login/getNum');
        //获取用户未读消息数
        $collector->post('/user/get-message',$basePath.'User/Login/getMessage');
        //添加用户可推送数
        $collector->post('/user/add-push-num',$basePath.'User/User/addPushNum');
        //用户用户推送数
        $collector->get('/user/get-push-num',$basePath.'User/User/getPushNum');
        //获取推送模板id
        $collector->get('/user/get-model-id',$basePath.'User/Login/getModelId');
        //获取钱包余额
        $collector->get('/user/wallet/get-money',$basePath.'User/Wallet/getMoney');
        //获取排行榜列表
        $collector->get('/user/leader-board-list',$basePath.'User/Login/getLeaderBoardList');

    }

    public function damage(RouteCollector $collector, string $basePath = ''){

        $collector->post('/test',$basePath.'Damage/Damage/test');
    }

    public function wa(RouteCollector $collector, string $basePath = ''){
        //获取wa tab列表信息
        $collector->get('/wa/get-tab-list',$basePath.'Wa/Wa/getTabList');
        //获取wa内容列表
        $collector->get('/wa/get-wa-list',$basePath.'Wa/Wa/getWaList');
        //获取wa详情
        $collector->get('/wa/get-wa-info',$basePath.'Wa/Wa/getWaInfo');
        //获取wa标签
        $collector->get('/wa/get-wa-label',$basePath.'Wa/Wa/getLabels');
        //获取wa评论
        $collector->get('/wa/get-comment',$basePath.'Wa/Wa/getWaComment');
        //进行评论
        $collector->post('/wa/to-comment',$basePath.'Wa/WaL/toComment');
        //删除评论
        $collector->post('/wa/del-comment',$basePath.'Wa/WaL/delComment');
        //获取wa收藏列表
        $collector->get('/wa/get-wa-favorites-list',$basePath.'Wa/WaL/getWaFavoritesList');
        //获取用户所有wa评论
        $collector->get('/wa/get-comment-all',$basePath.'Wa/WaL/getCommentAll');
        //保存爬虫数据
        $collector->post('/wa/save-fiddler-data',$basePath.'Wa/Wa/saveFiddlerData');
        //爬虫数据转移到正式
        $collector->post('/wa/save-python-wa',$basePath.'Wa/Wa/savePythonWa');

    }

    public function test(RouteCollector $collector, string $basePath = '')
    {
        $collector->get('/test',$basePath.'File/File/uploadImageToBlog');
        $collector->get('/test-new',$basePath.'Test/Test/test');
        $collector->post('/upload',$basePath.'File/File/uploadImage');
        $collector->post('/sync-redis',$basePath.'Test/Test/aKeySyncRedis');
        $collector->post('/hand-leader-board',$basePath.'Test/Test/handLeaderBoard');
        $collector->post('/test/collet-mount',$basePath.'Test/Test/collectMount');

    }

    public function chatRoom(RouteCollector $collector, string $basePath = ''){
        //获取聊天室历史记录
        $collector->get('/chat-room/get-history',$basePath.'Chat/Chat/getChatHistory');
        //获取房间当前成员
        $collector->get('/chat-room/get-member',$basePath.'Chat/Chat/getChatMember');
        //记录错误日志
        $collector->post('/chat-room/record-log',$basePath.'Chat/Chat/recordLog');
    }

    public function helpCenter(RouteCollector $collector, string $basePath = ''){
        //获取帮助列表
        $collector->post('/help-center/list',$basePath.'HelpCenter/HelpCenter/getHelpList');
        //添加帮助
        $collector->post('/help-center/add',$basePath.'HelpCenter/HelpCenterL/addHelp');
        //帮助详情
        $collector->get('/help-center/info',$basePath.'HelpCenter/HelpCenter/getHelpInfo');
        //回答列表
        $collector->get('/help-center/answer-list',$basePath.'HelpCenter/HelpCenter/getAnswerList');
        //采纳答案
        $collector->post('/help-center/adopt-answer',$basePath.'HelpCenter/HelpCenterL/adoptAnswer');
        //提交回答
        $collector->post('/help-center/set-answer-status',$basePath.'HelpCenter/HelpCenterL/setAnswerStatus');
        //修改求助回答
        $collector->post('/help-center/update-answer',$basePath.'HelpCenter/HelpCenterL/updateAnswer');
        //添加求助回答
        $collector->post('/help-center/add-answer',$basePath.'HelpCenter/HelpCenterL/addAnswer');
        //回答详情
        $collector->get('/help-center/get-answer-info',$basePath.'HelpCenter/HelpCenter/getAnswerInfo');
        //删除求助
        $collector->post('/help-center/del-help',$basePath.'HelpCenter/HelpCenterL/deleteHelp');
        //删除回答
        $collector->post('/help-center/del-answer',$basePath.'HelpCenter/HelpCenterL/delAnswer');
        //用户本人的回答列表
        $collector->get('/help-center/user-answer-list',$basePath.'HelpCenter/HelpCenterL/getUserAnswerList');
        //用户本人的帮助列表
        $collector->get('/help-center/user-list',$basePath.'HelpCenter/HelpCenterL/getUserHelpList');
        //获取有偿帮忙数量
        $collector->get('/help-center/get-pay-help-num',$basePath.'HelpCenter/HelpCenter/getPayHelpNum');

    }

    public function order(RouteCollector $collector, string $basePath = '')
    {
        //创建订单
        $collector->post('/order/add-order',$basePath.'Order/Order/addOrder');
        //订单日志
        $collector->get('/order/log-list',$basePath.'Order/Order/getLogList');

    }

    public function wxPay(RouteCollector $collector, string $basePath = '')
    {
        //支付回调
        $collector->post('/wx-pay/callback',$basePath.'Order/WxPay/wxPayCallback');
    }

    public function wxCallBack(RouteCollector $collector, string $basePath = '')
    {
        //消息回调
        $collector->get('/wx-callback',$basePath.'WxCallBack/WxCallBack/callBack');
    }

    public function mount(RouteCollector $collector, string $basePath = '')
    {
        //坐骑列表
        $collector->get('/mount/list',$basePath.'Mount/Mount/getList');
        //进行坐骑抽奖
        $collector->post('/mount/lottery',$basePath.'Mount/MountL/doLottery');
        //坐骑抽奖日志列表
        $collector->get('/mount/lottery-log-list',$basePath.'Mount/MountL/getLotteryLogList');
    }

    public function wallet(RouteCollector $collector, string $basePath = '')
    {
        //帮币转换幸运币
        $collector->post('/wallet/add-lucky-coin',$basePath.'Wallet/Wallet/transformMoney');
        //获取幸运币
        $collector->post('/wallet/get-lucky-coin',$basePath.'Wallet/Wallet/getLuckyCoin');
        //获取所有币值
        $collector->get('/wallet/get-coin',$basePath.'Wallet/Wallet/getCoin');

    }

    public function common(RouteCollector $collector, string $basePath = '')
    {
        //获取工具列表
        $collector->get('/tool/list',$basePath.'Common/Tool/getToolList');
        //获取工具子列表
        $collector->get('/tool/child-list',$basePath.'Common/Tool/getToolChildList');
    }

    public function macro(RouteCollector $collector, string $basePath = '')
    {
        //组合技能宏
        $collector->post('/macro/group',$basePath.'Common/Macro/group');
        //保存宏记录
        $collector->post('/macro/save',$basePath.'Common/Macro/save');
        //删除宏
        $collector->post('/macro/del',$basePath.'Common/Macro/del');
        //获取手动创建宏菜单列表
        $collector->get('/macro/tab-list',$basePath.'Common/Macro/getHandMacroList');
        //组合手动创建宏
        $collector->post('/macro/hand-combine',$basePath.'Common/Macro/handCombine');
        //用户宏列表
        $collector->get('/macro/list',$basePath.'Common/Macro/getList');
    }
}