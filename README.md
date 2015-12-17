Download [sms-phpstorm-config-installer](https://github.com/smstw/sms-phpstorm-config-installer/releases/download/0.1.0/sms-phpstorm-config-installer.phar)

Run commands to install PhpStorm configs:

```
$ php sms-phpstorm-config-installer.phar install
```

### 修改 config 檔清單

若是想直接在 GitHub 上新增一些 config 檔，除了將新增 config 檔之外，[config 清單檔案](https://github.com/smstw/sms-phpstorm-config-installer/blob/master/res/config-file-list.txt)要一併修改，修改好後就可以 push 到這個 repository

剛 push 上去後要等一段時間，GitHub 的 CDN 才會刷新，大概 10 分鐘左右剛做好的更新就可以使用了。

### Build phar file

下載 [`box.phar`](https://github.com/box-project/box2)

```
$ php box.phar build
```
