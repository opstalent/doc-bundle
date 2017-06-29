<?php


namespace Opstalent\DocBundle\Extractor;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor as BaseExtractor;
use Symfony\Component\Routing\Route;

/**
 * Class ApiDocExtractor
 * @package DocBundle\Extractor
 */
class ApiDocExtractor extends BaseExtractor
{
    /**
     * Returns an array of data where each data is an array with the following keys:
     *  - annotation
     *  - resource
     *
     * @param array $routes array of Route-objects for which the annotations should be extracted
     *
     * @param string $view
     * @return array
     */
    public function extractAnnotations(array $routes, $view = ApiDoc::DEFAULT_VIEW)
    {
        $array = [];
        $resources = [];
        $excludeSections = $this->container->getParameter('nelmio_api_doc.exclude_sections');

        foreach ($routes as $route) {
            if (!$route instanceof Route) {
                throw new \InvalidArgumentException(sprintf('All elements of $routes must be instances of Route. "%s" given', gettype($route)));
            }

            if ($method = $this->getReflectionMethod($route->getDefault('_controller'))) {
                if ($route instanceof Route && substr($route->getPath(), 0, 2) != "/_" && $route->getPath() != "/") {
                    $desc = explode("::", $route->getDefault('_controller'));
                    $security = $route->getOption('security');

                    $annotation = $this->reader->getMethodAnnotations($method);
                    if(array_key_exists('0', $annotation) && $annotation[0] instanceof ApiDoc)
                    {
                        $apiDoc = $annotation[0];
                    } else {
                        $apiDoc = new ApiDoc([
                            "resource" => true,
                            "section" => ucfirst(strtok($route->getPath(), '/')),
                            "description" => next($desc),
                            "statusCodes" => ["200" => "OK"],
                            "authentication" => is_array($security) && array_key_exists('roles', $security),
                            "authenticationRoles" => is_array($security) && array_key_exists('roles', $security) ? $security['roles'] : [],
                            "input" => $route->getOption('form'),
                            "filters" => (count($route->getMethods()) == 1 && $route->getMethods()[0] === "GET" && !$route->getRequirement('id')) ? [
                                ["name" => "filter[offset]", "Type" => "integer"],
                                ["name" => "filter[limit]", "Type" => "integer"],
                                ["name" => "filter[orderBy]", "Type" => "string"],
                                ["name" => "filter[order]", "Type" => "string"],
                                ["name" => "filter[count]", "Type" => "bool"],
                            ] : [],
                            'parameters' => [],
                            'requirements' => []
                        ]);
                    }
                    $array[] = ['annotation' => $this->extractData($apiDoc, $route, $method)];
                } else {
                    $annotation = $this->reader->getMethodAnnotation($method, self::ANNOTATION_CLASS);
                    if (
                        $annotation && !in_array($annotation->getSection(), $excludeSections) &&
                        (in_array($view, $annotation->getViews()) || (0 === count($annotation->getViews()) && $view === ApiDoc::DEFAULT_VIEW))
                    ) {
                        if ($annotation->isResource()) {
                            if ($resource = $annotation->getResource()) {
                                $resources[] = $resource;
                            } else {
                                // remove format from routes used for resource grouping
                                $resources[] = str_replace('.{_format}', '', $route->getPath());
                            }
                        }

                        /** @var ApiDoc $annotation */
                        $array[] = ['annotation' => $this->extractData($annotation, $route, $method)];
                    }
                }
            }
        }

        foreach ($this->annotationsProviders as $annotationProvider) {
            foreach ($annotationProvider->getAnnotations() as $annotation) {
                $route = $annotation->getRoute();
                $array[] = ['annotation' => $this->extractData($annotation, $route, $this->getReflectionMethod($route->getDefault('_controller')))];
            }
        }

        rsort($resources);
        foreach ($array as $index => $element) {
            $hasResource = false;
            /** @noinspection PhpUndefinedMethodInspection */
            $path = $element['annotation']->getRoute()->getPath();

            foreach ($resources as $resource) {
                /** @noinspection PhpUndefinedMethodInspection */
                if (0 === strpos($path, $resource) || $resource === $element['annotation']->getResource()) {
                    $array[$index]['resource'] = $resource;

                    $hasResource = true;
                    break;
                }
            }

            if (false === $hasResource) {
                $array[$index]['resource'] = 'others';
            }
        }

        $methodOrder = ['GET', 'POST', 'PUT', 'DELETE'];
        usort($array, function ($a, $b) use ($methodOrder) {
            if ($a['resource'] === $b['resource']) {
                /** @noinspection PhpUndefinedMethodInspection */
                /** @noinspection PhpUndefinedMethodInspection */
                if ($a['annotation']->getRoute()->getPath() === $b['annotation']->getRoute()->getPath()) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $methodA = array_search($a['annotation']->getRoute()->getMethods(), $methodOrder);
                    /** @noinspection PhpUndefinedMethodInspection */
                    $methodB = array_search($b['annotation']->getRoute()->getMethods(), $methodOrder);

                    if ($methodA === $methodB) {
                        /** @noinspection PhpUndefinedMethodInspection */
                        /** @noinspection PhpUndefinedMethodInspection */
                        return strcmp(
                            implode('|', $a['annotation']->getRoute()->getMethods()),
                            implode('|', $b['annotation']->getRoute()->getMethods())
                        );
                    }

                    return $methodA > $methodB ? 1 : -1;
                }

                /** @noinspection PhpUndefinedMethodInspection */
                /** @noinspection PhpUndefinedMethodInspection */
                return strcmp(
                    $a['annotation']->getRoute()->getPath(),
                    $b['annotation']->getRoute()->getPath()
                );
            }

            return strcmp($a['resource'], $b['resource']);
        });

        return $array;
    }
}
