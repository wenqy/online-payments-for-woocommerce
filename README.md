# online-payments-for-woocommerce
wordpress woocommerce 银联在线支付插件

# 介绍 #
由于没有开源的wordpress、woocommerce银联支付插件，就定制了一个银联在线支付插件，wordpress plugin 地址：
[https://wordpress.org/plugins/online-payments-for-woocommerce/](https://wordpress.org/plugins/online-payments-for-woocommerce/ "https://wordpress.org/plugins/online-payments-for-woocommerce/")

# 前提 #
为PHP wordpress woocommerce 插件所依赖，必先安装wordpress，激活woocommerce，再安装本插件

# 特点 #
* WooCommerce子插件，完美兼容
* 在WooCommerce添加银联支付网关
* 支持汇率换算，需要手动设置汇率
* 支持退款功能
* 适合任意WooCommerce主题
* 帮助：[http://wenqy.com](http://wenqy.com "wenqy.com")

# 安装 #
1.  申请银联商户相关信息，安装前需要将银联在线支付的公钥、私钥证书放到插件的certs目录下.
2.  安装 "Online for WooCommerce" wordpress plugin 即本插件.
3.  激活.
4.  设置银联相关信息，如商户代码等等，默认值为银联测试环境账号 <strong>Woocommerce -> Settings -> Payment Gateways -> OnlinePay</strong>.
   You can apply online payment through https://open.union.com
5. 如果支付方式不是RMB，需设置汇率.
6. 帮助:http://wenqy.com

