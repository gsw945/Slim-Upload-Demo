# Slim-Upload-Demo
-----------------------------
基于 [Slim-Skeleton](https://github.com/slimphp/Slim-Skeleton)

#### 访问
* `/file/single/` 单文件上传
* `/file/multiple/` 多文件上传

#### API
* `/file/upload/` 上传地址
    - `field` form表单中，`input[type="file"]`元素的name
    - `hash` 客户端唯一UUID
    - 要上传的文件
* `/file/visit/?filename=<store下的文件名>`

#### Web框架
* [Slim Documentation](https://www.slimframework.com/docs/)
* [Slim框架-中文文档](https://slimphp.app/docs/)