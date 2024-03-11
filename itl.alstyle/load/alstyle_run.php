<?
//<title>Al-Style</title>
/** @global string $tmpid */

/** @global int $IBLOCK_ID */
/** @global array $arIBlock */

use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Catalog,
	Bitrix\Iblock,
	Bitrix\Main\Web\HttpClient,
	Bitrix\Main\Web\Json;

Loader::includeModule("currency");
Loader::includeModule("iblock");
Loader::includeModule("catalog");

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/catalog/import_setup_templ.php');

$startImportExecTime = getmicrotime();

global $USER;
global $APPLICATION;

$bTmpUserCreated = false;
if (!CCatalog::IsUserExists()) {
	$bTmpUserCreated = true;
	if (isset($USER))
		$USER_TMP = $USER;
	$USER = new CUser();
}

$strImportErrorMessage = "";
$strImportOKMessage = "";

$accessToken = COption::GetOptionString("itl.alStyle", "ITL_ALSTYLE_ACCESS_TOKEN");
if(empty($accessToken)) {
	$strImportErrorMessage .= 'Ошибка: В настройках модуля не указан токен доступа к API';
}

// проверка наличия инфоблока, в который будут импортироваться товары
if (empty($strImportErrorMessage)) {
	$max_execution_time = (isset($max_execution_time) ? (int)$max_execution_time : 0);
	if ($max_execution_time <= 0)
		$max_execution_time = 0;
	if (defined('BX_CAT_CRON') && true == BX_CAT_CRON)
		$max_execution_time = 0;

	if (defined("CATALOG_LOAD_NO_STEP") && CATALOG_LOAD_NO_STEP)
		$max_execution_time = 0;

	$IBLOCK_ID = intval($IBLOCK_ID);
	if ($IBLOCK_ID <= 0) {
		$strImportErrorMessage .= GetMessage("CATI_NO_IBLOCK") . "<br>";
	} else {
		$arIBlock = CIBlock::GetArrayByID($IBLOCK_ID);
		if (false === $arIBlock) {
			$strImportErrorMessage .= GetMessage("CATI_NO_IBLOCK") . "<br>";
		}
	}
}

// Проверка, является ли выбранный инфоблок торговым каталогом
$bWorkflow = false;
if (empty($strImportErrorMessage)) {
	$bWorkflow = CModule::IncludeModule("workflow") && ($arIBlock["WORKFLOW"] != "N");
	$bIBlockIsCatalog = false;
	$arSku = false;
	$rsCatalogs = CCatalog::GetList(
		array(),
		array('IBLOCK_ID' => $IBLOCK_ID),
		false,
		false,
		array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID', 'SKU_PROPERTY_ID')
	);
	if ($arCatalog = $rsCatalogs->Fetch()) {
		$bIBlockIsCatalog = true;
		$arCatalog['IBLOCK_ID'] = (int)$arCatalog['IBLOCK_ID'];
		$arCatalog['PRODUCT_IBLOCK_ID'] = (int)$arCatalog['PRODUCT_IBLOCK_ID'];
		$arCatalog['SKU_PROPERTY_ID'] = (int)$arCatalog['SKU_PROPERTY_ID'];
		if ($arCatalog['PRODUCT_IBLOCK_ID'] > 0  && $arCatalog['SKU_PROPERTY_ID'] > 0) {
			$arSku = $arCatalog;
		}
	} else {
		$strImportErrorMessage .= 'Ошибка: Выбранный инфоблок не является торговым каталогом.';
	}
}

$IMAGE_RESIZE = (isset($IMAGE_RESIZE) && $IMAGE_RESIZE == 'Y' ? 'Y' : 'N');

$currentUserID = $USER->GetID();
$defaultMeasureId = null;

if (empty($strImportErrorMessage)) {
	$boolUseStoreControl = Catalog\Config\State::isUsedInventoryManagement(); // Возвращает true, если управление складскими запасами разрешено и включено.
	$measure = CCatalogMeasure::getDefaultMeasure();
	if (!empty($measure)) {
		if ($measure['ID'] > 0) {
			$defaultMeasureId = $measure['ID'];
		}
	}
	unset($measure);

	Iblock\PropertyIndex\Manager::enableDeferredIndexing();
	Catalog\Product\Sku::enableDeferredCalculation();

	// Создание пользовательского св-ва для хранения ID категории
	$hasFieldAlStyleCatID = false;
	global $USER_FIELD_MANAGER;
	$userFields = $USER_FIELD_MANAGER->GetUserFields("IBLOCK_" . $IBLOCK_ID . "_SECTION");
	if (!empty($userFields)) {
		foreach ($userFields as $userField) {
			if ($userField['$userField'] === 'UF_ALSTYLE_CAT_ID') {
				$hasFieldAlStyleCatID = true;
				break;
			}
		}
	}

	$userTypeEntity = new CUserTypeEntity();
	$userFieldId = 0;
	if (!$hasFieldAlStyleCatID) {
		$aUserFields = array(
			'ENTITY_ID' => 'IBLOCK_' . $IBLOCK_ID . '_SECTION',
			'FIELD_NAME' => 'UF_ALSTYLE_CAT_ID',
			'USER_TYPE_ID' => 'string',
			'XML_ID' => 'XML_ID_ALSTYLE_CAT_ID',
			'SORT' => 500,
			'MULTIPLE' => 'N',
			'MANDATORY' => 'N',
			'SHOW_FILTER' => 'N',
			'SHOW_IN_LIST' => '',
			'EDIT_IN_LIST' => 'N',
			'IS_SEARCHABLE' => 'N',
			'SETTINGS' => array(
				'DEFAULT_VALUE' => '',
				'SIZE' => '20',
				'ROWS' => '1',
				'MIN_LENGTH' => '0',
				'MAX_LENGTH' => '0',
				'REGEXP' => '',
			),
			'EDIT_FORM_LABEL' => array(
				'ru' => 'ID категории в Al-Style',
				'en' => 'Category ID in Al-Style',
			),
			'LIST_COLUMN_LABEL' => array(
				'ru' => 'ID категории в Al-Style',
				'en' => 'Category ID in Al-Style',
			),
			'LIST_FILTER_LABEL' => array(
				'ru' => 'ID категории в Al-Style',
				'en' => 'Category ID in Al-Style',
			),
			'ERROR_MESSAGE' => array(
				'ru' => 'Ошибка при заполнении пользовательского свойства',
				'en' => 'An error in completing the user field',
			),
			'HELP_MESSAGE' => array(
				'ru' => '',
				'en' => '',
			),
		);
		$userFieldId = $userTypeEntity->Add($aUserFields);
		$hasFieldAlStyleCatID = true;
	}

	if (!$hasFieldAlStyleCatID) {
		$strImportErrorMessage .= 'Нет пользовательского свойства для хранения ID категории';
	}
}

// проверка актуальности данных
if (empty($strImportErrorMessage)) {
	$http = new HttpClient();
	$relevanceResult = $http->get('https://api.al-style.kz/api/date?access-token=' . urlencode($accessToken));
	try {
		$relevance = Json::decode($relevanceResult);
	} catch (\Bitrix\Main\SystemException $e) {
		$relevance = false;
	}

	if (!$relevance) {
		$strImportErrorMessage .= 'Не подтверждена актуальность данных';
	}
}

// получение списка категорий из API
$alStyleSections = [];
if (empty($strImportErrorMessage)) {
	$sectionsResult = $http->get('https://api.al-style.kz/api/categories?access-token=' . urlencode($accessToken));
	$alStyleSections = Json::decode($sectionsResult);

	if (empty($alStyleSections)) {
		$strImportErrorMessage .= 'Не удалось получить список категорий из API!';
	}
}

// проверка, есть ли категории из Al-Style на сайте.
// Сначала проверяется по значению польз. поля, в котором хранится ID категории.
// Если таких категорий нет, то проверяется по названию категории
$arSections = [];
$arAlStyleSections = [];
$alStyleSectionsNames = [];
$alStyleSectionsIDs = [];
if (empty($strImportErrorMessage)) {
	foreach ($alStyleSections as $alStyleSection) {
		$arAlStyleSections[$alStyleSection['id']] = $alStyleSection;
		$arAlStyleSections[$alStyleSection['id']]['SECTION_INFO'] = [];
	}

	foreach ($arAlStyleSections as $alStyleSection) {
		$alStyleSectionsNames[$alStyleSection['id']] = $alStyleSection['name'];
		$alStyleSectionsIDs[] = $alStyleSection['id'];
	}

	$arFilter = [
		'IBLOCK_ID' => $IBLOCK_ID,
		'UF_ALSTYLE_CAT_ID' => $alStyleSectionsIDs
	];
	$arSelect = ['ID', 'IBLOCK_ID', 'DEPTH_LEVEL', 'NAME', 'UF_ALSTYLE_CAT_ID'];
	$dbSections = CIBlockSection::GetList(array("ID" => "ASC"), $arFilter, false, $arSelect, false);
	while ($arSection = $dbSections->GetNext()) {
		$arSections[] = $arSection;
	}

	// поиск новых категорий, которые еще не участвовали в импорте
	$arFilter = [
		'IBLOCK_ID' => $IBLOCK_ID,
		'NAME' => $alStyleSectionsNames,
		'UF_ALSTYLE_CAT_ID' => false
	];
	$dbSections = CIBlockSection::GetList(array("ID" => "ASC"), $arFilter, false, $arSelect, false);
	while ($arSection = $dbSections->GetNext()) {
		$arSections[] = $arSection;
	}

	if (empty($arSections)) {
		$strImportErrorMessage .= 'На сайте нет нужных категорий!';
	}
}

$alStyleProductsAPI = [];
$alStyleProducts = [];
$alStyleProdArticles = [];
$products = [];
$limit = 250;
$offset = 0;
$productAddFields = 'description,brand,images,barcode,discountPrice,discount,detailText,defectDescription';
if (empty($strImportErrorMessage)) {
	$catalogGroupId = 0;
	$rsGroup = \Bitrix\Catalog\GroupLangTable::getList(array(
		'filter' => array('LANG' => LANGUAGE_ID),
		'select' => array('NAME', 'XML_ID' => 'CATALOG_GROUP.XML_ID', 'CATALOG_GROUP_ID')
	));

	if ($arPriceLangGroup = $rsGroup->fetch()) {
		$catalogGroupId = $arPriceLangGroup['CATALOG_GROUP_ID'];
	}

	$totalCountProducts = 0; // общее кол-во товаров, находящихся в категориях с товарами AlStyle
	$countProducts = 0; // кол-во товаров, которое уже получено из API
	$alStyleSectionIDs = [];
	foreach ($arSections as $key => $section) {
		if (!empty($section['UF_ALSTYLE_CAT_ID'])) {
			$alStyleSectionID = $section['UF_ALSTYLE_CAT_ID'];
		} else {
			// заполнение польз. поля "ID категории в Al-Style", если оно пустое
			$alStyleSectionID = array_search($section['NAME'], $alStyleSectionsNames);
			$USER_FIELD_MANAGER->Update('IBLOCK_' . $IBLOCK_ID . '_SECTION', $section['ID'], array(
				'UF_ALSTYLE_CAT_ID' => $alStyleSectionID
			)); // boolean

			$arSections[$key]['UF_ALSTYLE_CAT_ID'] = $alStyleSectionID;
		}

		$alStyleSectionIDs[] = $alStyleSectionID;
		$totalCountProducts += $arAlStyleSections[$alStyleSectionID]['elements'];
		$arAlStyleSections[$alStyleSectionID]['SECTION_INFO'] = $section;
	}

	// получение товаров по API (можно получать максимум 250 товаров за один запрос)
	while ($totalCountProducts >= $countProducts) {
		$productRequest = 'https://api.al-style.kz/api/elements?access-token=' . urlencode($accessToken) . '&category=' . implode(',', $alStyleSectionIDs) . '&limit=' . $limit . '&offset=' . $offset . '&additional_fields=' . $productAddFields;
		$productsResult = $http->get($productRequest);
		if (empty($productsResult)) {
			$strImportErrorMessage .= 'Не удалось получить список товаров из API!';
			break;
		}

		$products = Json::decode($productsResult);
		if (is_array($products) && !empty($products)) {
			$countProducts += count($products);
			$offset = $countProducts;
			$alStyleProductsAPI = array_merge($alStyleProductsAPI, $products);
			$products = [];
		} else {
			break;
		}
	}

	if (empty($alStyleProductsAPI)) {
		$strImportErrorMessage .= 'Не удалось получить список товаров из API!';
	}

	foreach ($alStyleProductsAPI as $product) {
		$alStyleProducts[$product['article']] = $product;
		$alStyleProdArticles[] = $product['article'];
	}
}

// импорт свойств товаров (импортируются св-ва только новых товаров)
if (empty($strImportErrorMessage)) {
	// получение списка всех св-в каталога
	$iblockProps = CIBlockProperty::GetList(array("sort" => "asc", "name" => "asc"), array("IBLOCK_ID" => $IBLOCK_ID));
	$iblockPropsCodes = [];
	while ($propFields = $iblockProps->GetNext()) {
		$iblockPropsCodes[] = $propFields['CODE'];
	}

	$alStyleProductsWithProps = [];
	if (!empty($alStyleProdArticles)) {
		$groupProdArticles = array_chunk($alStyleProdArticles, 10);

		// получение списка товаров со свойствами из API
		foreach ($groupProdArticles as $groupArticle) {
			$strArticles = implode(',', $groupArticle);
			$propsRequest = 'https://api.al-style.kz/api/properties?access-token=' . urlencode($accessToken) . '&article=' . $strArticles;
			$propsResult = $http->get($propsRequest);
			if (!empty($propsResult)) {
				$result = Json::decode($propsResult);
				if (isset($result['elements']) && !empty($result['elements'])) {
					$alStyleProductsWithProps = array_merge($alStyleProductsWithProps, $result['elements']);
				}
			} else {
				$strImportErrorMessage .= 'Не удалось получить список свойств товаров из API!';
			}
		}
	}

	if (!empty($alStyleProductsWithProps)) {
		$iblockProp = new CIBlockProperty;
		foreach ($alStyleProductsWithProps as $productWithProps) {
			if (empty($productWithProps['properties'])) {
				continue;
			}

			foreach ($productWithProps['properties'] as $prop) {
				// если свойства нет в инфоблоке каталога, то создаем его
				if (!in_array($prop['code'], $iblockPropsCodes)) {
					$propFields = array(
						"NAME" => $prop['name'],
						"ACTIVE" => "Y",
						"SORT" => "500",
						"CODE" => $prop['code'],
						"PROPERTY_TYPE" => "S",
						"IBLOCK_ID" => $IBLOCK_ID,
					);

					$propID = $iblockProp->Add($propFields);
					if(!empty($propID)) {
						$iblockPropsCodes[] = $prop['code'];
					}
				}
			}

			// добавление в массив с товарами из API массива свойств
			$alStyleProducts[$productWithProps['article']]['properties'] = $productWithProps['properties'];
		}
	}
}

$currency = COption::GetOptionString("sale", "default_currency", "KZT");
if (empty($strImportErrorMessage)) {
	$obElement = new CIBlockElement();
	foreach ($alStyleProducts as $key => $product) {
		$productID = 0;
		// проверка, есть ли уже такой товар на сайте
		$code = CUtil::translit($product['name'], LANGUAGE_ID);
		$arFilter = [
			'IBLOCK_ID' => $IBLOCK_ID,
			'CODE' => $code,
			'PROPERTY_CML2_ARTICLE' => $product['article'],
		];

		$dbProductResult = CIBlockElement::GetList(
			array(),
			$arFilter,
			false,
			false,
			array('ID', 'IBLOCK_SECTION_ID')
		);

		if ($arProductResult = $dbProductResult->Fetch()) {
			$productID = $arProductResult['ID'];
		} else {
			$iblockSectionID = $arAlStyleSections[$product['category']]['SECTION_INFO']['ID'];

			$arFields = [
				'IBLOCK_ID' => $IBLOCK_ID,
				'IBLOCK_SECTION_ID' => $iblockSectionID,
				'MODIFIED_BY' => $currentUserID,
				'NAME' => $product['name'],
				'CODE' => $code,
				'ACTIVE' => 'Y',
				'DETAIL_TEXT_TYPE' => 'html',
				'DETAIL_TEXT' => isset($product['detailText']) ? $product['detailText'] : '',
				'DETAIL_PICTURE' => $product['detailText'],
				'PROPERTY_VALUES' => [
					'ARTNUMBER' => $product['article'],
					'MANUFACTURER' => $product['brand'],
				]
			];

			if (isset($product['images']) && is_array($product['images'])) {
				$arFields['PREVIEW_PICTURE'] = CFile::MakeFileArray($product['images'][0]);
				$arFields['DETAIL_PICTURE'] = CFile::MakeFileArray($product['images'][0]);
				unset($alStyleProducts[$key]['images'][0]);
			}

			$productID = $obElement->Add($arFields, $bWorkflow, false, 'Y' === $IMAGE_RESIZE);
			if(empty($productID)) {
				$strImportErrorMessage .= 'Ошибка добавления товара';
				continue;
			}

			if (isset($product['images']) && is_array($product['images'])) {
				$arFiles = [];
				foreach ($product['images'] as $image) {
					$arFiles[] = [
						'VALUE' => CFile::MakeFileArray($image),
						'DESCRIPTION' => ''
					];
				}
				CIBlockElement::SetPropertyValueCode($productID, 'MORE_PHOTO', $arFiles);
			}

			if(isset($product['properties']) && !empty($product['properties'])) {
				foreach ($product['properties'] as $property) {
					CIBlockElement::SetPropertyValuesEx(
						$productID,
						$IBLOCK_ID,
						array($property['code'] => $property['value'])
					);
				}
			}
		}

		$quantity = $product['quantity'];
		if (strpos($quantity, '>') !== false) {
			$quantity = str_replace('>', '', $quantity);
			$quantity++;
		}

		$productFields = [
			'fields' => [
				'ID' => $productID,
				'QUANTITY' => $quantity,
			],
			'external_fields' => [
				'IBLOCK_ID' => $IBLOCK_ID
			]
		];

		$productRow = Catalog\Model\Product::getList(array(
			'select' => array('ID'),
			'filter' => array('=ID' => $productID)
		))->fetch();

		if (empty($productRow)) {
			$productFields['fields']['MEASURE'] = $defaultMeasureId;
			$productFields['fields']['QUANTITY_RESERVED'] = 0;
			$productFields['fields']['QUANTITY_TRACE'] = \Bitrix\Catalog\ProductTable::STATUS_DEFAULT;
			$productFields['fields']['CAN_BUY_ZERO'] = \Bitrix\Catalog\ProductTable::STATUS_DEFAULT;
			$productFields['fields']['TYPE'] = \Bitrix\Catalog\ProductTable::TYPE_PRODUCT;

			$productResult = Catalog\Model\Product::add($productFields);
			if (!$productResult->isSuccess()) {
				$strImportErrorMessage .= implode('. ', $productResult->getErrorMessages());
			}
		} else {
			$productResult = Catalog\Model\Product::update($productID, $productFields);
			if (!$productResult->isSuccess()) {
				$strImportErrorMessage .= implode('. ', $productResult->getErrorMessages());
			}
		}

		$price = $product['price2'];
		$priceResult = \Bitrix\Catalog\Model\Price::getList(array(
			'filter' => array('CATALOG_GROUP_ID' => $catalogGroupId, 'PRODUCT_ID' => $productID),
		));

		if ($arPriceResult = $priceResult->fetch()) {
			if (!empty($price)) {
				$result = \Bitrix\Catalog\Model\Price::update($arPriceResult['ID'], array(
					'PRICE' => $price,
					'PRICE_SCALE' => $price,
				));
			} else {
				$result = \Bitrix\Catalog\Model\Price::delete($arPriceResult['ID']);
			}
		} else {
			$result = \Bitrix\Catalog\Model\Price::add(array(
				'CATALOG_GROUP_ID' => $catalogGroupId,
				'PRODUCT_ID' => $productID,
				'PRICE' => $price,
				'PRICE_SCALE' => $price,
				'CURRENCY' => $currency,
			));
		}

		if (!$result->isSuccess()) {
			$strImportErrorMessage .= implode('. ', $productResult->getErrorMessages());
		}
	}

	Catalog\Product\Sku::disableDeferredCalculation();
	Catalog\Product\Sku::calculate();
	Iblock\PropertyIndex\Manager::disableDeferredIndexing();
	Iblock\PropertyIndex\Manager::runDeferredIndexing($IBLOCK_ID);
}

if ($bTmpUserCreated) {
	if (isset($USER_TMP)) {
		$USER = $USER_TMP;
		unset($USER_TMP);
	}
}