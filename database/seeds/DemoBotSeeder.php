<?php

use App\Bot;
use App\Helpers\S3BucketHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoBotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $name                       = 'demo-bot';
        $description                = 'test';
        $type                       = 'public';
        $s3_path                    = 'scripts/demo-bot';
        $custom_script              = '
/** PARAMS
{
    "youtubeLink": {
        "type": "string",
        "title": "Youtube Link",
        "description": "desc",
        "icon": "fa-user"
    },

    "delay": {
        "type": "string",
        "title": "Delay",
        "description": "desc",
        "icon": "fa-user"
    },
    "delayUnit": {
        "type": "string",
        "title": "Delay unit",
        "description": "desc",
        "icon": "fa-key"
    }
}
*/
const puppeteer = require(\'puppeteer\');
const moment = require(\'moment\');
const fs = require(\'fs\');

const {
  youtubeLink: {
    value: youtubeLink
  } = { value: undefined },
  delay: {
    value: delay
  } = { value: undefined },
  delayUnit: {
    value: delayUnit
  } = { value: undefined}
} = params || {};

const ms = moment
  .duration(delay, delayUnit)
  .asMilliseconds();

const dirImage = \'./output/images/\';
const fileJson = moment().format("YYYYMMDDhhmmss") + \'.json\';
const dirJson = \'./output/json/\';
const linkJson = dirJson + fileJson;

const prepareIntervalWork = async () => {
  try {
    const timeNow = moment().format("YYYY-MM-DD hh:mm:ss");
    const content = fs.readFileSync(linkJson);
    const logs = JSON.parse(content);
    const keys = Object.keys(logs);
    const convertType = keys.map(k => Number(k));
    const prevMax = Math.max(...convertType, 0);
    const nextMax = prevMax + 1;
    notify(\'screenshot added\');
    logs[nextMax] = \'screenshot added in \' + timeNow;
    const json = JSON.stringify(logs, null, 4);
    fs.writeFileSync(linkJson, json);
  } catch (e) {
    console.log(\'The output file could not be written\');
  }
};

const run = async () => {
  try {
    if (!fs.existsSync(dirJson)) {
      notify(\'json dir init\');
      fs.mkdirSync(dirJson, { recursive: true });
    }

    if (!fs.existsSync(dirImage)) {
      notify(\'images dir init\');
      fs.mkdirSync(dirImage, { recursive: true });
    }

    fs.writeFile(linkJson, JSON.stringify({}), () => {
      notify(\'json file init\');
    })

    const browser = await puppeteer.launch({
      headless: false,
      args: [\'--window-size=800,500\']
    });

    const page = await browser.newPage();
    await page.setViewport({width: 800, height: 500, deviceScaleFactor: 2});
    notify(\'browser open\');
    await page.goto(youtubeLink);
    notify(\'link open\');
    await page.click(
      "#movie_player > div.ytp-chrome-bottom > div.ytp-chrome-controls > div.ytp-left-controls > button[aria-label=\'Play (k)\'"
    );
    notify(\'video play\');

    setInterval(() => {
      prepareIntervalWork();
      page.screenshot({
        path: `${dirImage}${moment().format("YYYY-MM-DD hh:mm:ss")}.png`,
      });
    }, ms);
  } catch (e) {
    browser.close();
    console.log(e);
  }
};

run().catch(e => {
  browser.close();
  console.log(e);
});
        ';

        $aws_custom_package_json    = '
{
  "name": "demo-bot",
  "version": "0.0.1",
  "author": "80bots",
  "description": "",
  "license": "ISC",
  "bugs": {
    "url": "https://github.com/80bots/issues"
  },
  "dependencies": {
    "caniuse-db": "^1.0.30000921",
    "chalk": "^2.3.2",
    "chrome-har": "^0.7.1",
    "chrome-launcher": "^0.13.4",
    "cli-table": "^0.3.1",
    "del": "^3.0.0",
    "express": "^4.16.4",
    "fs": "0.0.1-security",
    "lighthouse": "^4.0.0-alpha.2-3.2.1",
    "mime": "^2.4.0",
    "moment": "^2.26.0",
    "node-fetch": "^2.3.0",
    "pixel-diff": "^1.0.1",
    "pngjs": "^3.3.3",
    "puppeteer": "^1.11.0",
    "request": "^2.88.0",
    "request-promise": "^4.2.2",
    "request-promise-native": "^1.0.5",
    "resize-img": "^1.1.2",
    "sharp": "^0.26.0",
    "ws": "^6.1.2",
    "yargs": "^12.0.5"
  }
}
        ';

        $parameters = S3BucketHelper::extractParamsFromScript($custom_script);
        $path       = Str::slug($name, '_') . '.custom.js';

        S3BucketHelper::deleteFilesS3(
            $s3_path
        );

        $bot = Bot::create([
            'name'              => $name,
            'description'       => $description,
            'parameters'        => $parameters,
            'path'              => $path,
            's3_path'           => $s3_path,
            'type'              => $type,
        ]);

        S3BucketHelper::updateOrCreateFilesS3(
            $bot,
            Storage::disk('s3'),
            $custom_script,
            $aws_custom_package_json,
        );

    }
}
