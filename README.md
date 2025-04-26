# Imposter Autoload Patcher

This package aims for helping [imposter](https://github.com/typisttech/imposter)
when developers want to use patched classes by imposter in runtime.

This script will read `autoload_static.php` file and replace old namespaces with
patched namespaces.

## Config

In your `composer.json`,

```
"extra": {
  "imposterAutoloadPatcher": {
    "prefix": "My\\App\\Vendor\\",
    "targets": [
      "Psr\\",
      "Bojaghi\\"
    ]
  }
}
```

### extra.imposterAutoloadPatcher.prefix

*Required* string

This is just like `extra.imposter.namespace`. Namespace prefix to be added.
Namespace may end with or without a backslash.

### extra.imposterAutoloadPatcher.targets

*Optional* Array of strings

Target namespaces. No need to be fully-qualified.
For example, you have two required packages in runtime, `Psr\Container` and `Psr\Log`.
And if these are all the packages starting with `Psr` in your `composer.json`, then `Psr\\` is just enough.

Every string may end with or without a backslash.

### Add a command event script

Add scripts.post-autoload-dump event script.

```
"scripts": {
  "post-autoload-dump": "vendor/bin/imposter-autoload-patcher"
}
```
