<?php

namespace Pumukit\NewAdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\NewAdminBundle\Form\Type\TagType;

class TagController extends Controller
{
    /**
     *
     * @Template
     */
    public function indexAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $root_name = "ROOT";
        $root = $repo->findOneByCod($root_name);

        if (null !== $root) {
            $children = $root->getChildren();
        } else {
            $children = array();
        }

        return array('root' => $root,
           'children' => $children, );
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     * @Template
     */
    public function childrenAction(Tag $tag, Request $request)
    {
        return array('tag' => $tag,
           'children' => $tag->getChildren(), );
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     */
    public function deleteAction(Tag $tag, Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        if (0 == $num = count($tag->getChildren())) {
            $dm->remove($tag);
            $dm->flush();

            return new JsonResponse(array("status" => "Deleted"), 200);
        }

        return new JsonResponse(array("status" => "Tag with children (".$num.")"), 404);
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     * @Template
     */
    public function updateAction(Tag $tag, Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $translator = $this->get('translator');
        $locale = $request->getLocale();
        $form = $this->createForm(new TagType($translator, $locale), $tag);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $tag = $this->get('pumukitschema.tag')->updateTag($tag);
            } catch (\Exception $e) {
                return new JsonResponse(array("status" => $e->getMessage()), 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
        }

        return array('tag' => $tag, 'form' => $form->createView());
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag", options={"id" = "parent"})
     * @Template
     */
    public function createAction(Tag $parent, Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $tag = new Tag();
        $tag->setParent($parent);

        $translator = $this->get('translator');
        $locale = $request->getLocale();

        $form = $this->createForm(new TagType($translator, $locale), $tag);

        if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
            try {
                $dm->persist($tag);
                $dm->flush();
            } catch (\Exception $e) {
                return new JsonResponse(array("status" => $e->getMessage()), 409);
            }

            return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
        }

        return array('tag' => $tag, 'form' => $form->createView());
    }

    /**
     * List action
     * @Template
     */
    public function listAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $root_name = "ROOT";
        $root = $repo->findOneByCod($root_name);

        if (null !== $root) {
            $children = $root->getChildren();
        } else {
            $children = array();
        }

        return array(
                     'root' => $root,
                     'children' => $children
                     );
    }

    public function batchDeleteAction(Request $request)
    {
        $dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $ids = $this->getRequest()->get('ids');

        if ('string' === gettype($ids)){
            $ids = json_decode($ids, true);
        }

        $tags = array();
        $tagsWithChildren = array();
        foreach ($ids as $id) {
            $tag = $repo->find($id);
            if (0 == count($tag->getChildren())) {
                $tags[] = $tag;
            }else{
                $tagsWithChildren[] = $tag;
            }
        }

        if (0 !== count($tagsWithChildren)){
            $message = '';
            foreach($tagsWithChildren as $tag){
                $message .= "Tag '".$tag->getCod()."' with children (".count($tag->getChildren())."). ";
            }

            return new JsonResponse(array("status" => $message), 404);
        }else{
            foreach ($tags as $tag){
                $dm->remove($tag);
            }
            $dm->flush();
        }

        $this->addFlash('success', 'delete');

        return $this->redirect($this->generateUrl('pumukitnewadmin_tag_list'));
    }
    
    
    /**
     * Search action
     * returns the tags matching with the search_text parameter.
     */
    /*public function searchAction(Request $request)
    {
        $search_text=$request->get('search_text');
		$dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

        $children = $repo->findByName($search_text);

        return array(
                     'children' => $children
                     );
    }*/
    public function searchAction(Request $request)
    {
		$dm = $this->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository('PumukitSchemaBundle:Tag');
		$search_text=$request->get('search_text');
		$lang= $request->getLocale();
		
		$mmId = $request->get('mmId');
		$parent = $repo->findOneById($request->get('parent'));
		$parent_path = str_replace( "|", "\|", $parent->getPath());
        $root_name = "ROOT";
        $root = $repo->findOneByCod($root_name);

        if (null !== $root) {
			$qb = $dm->createQueryBuilder('PumukitSchemaBundle:Tag');
			$children = $qb->addOr($qb->expr()->field("title.".$lang)->equals(new \MongoRegex('/.*'.$search_text.'.*/i')))
						->addOr($qb->expr()->field("cod")->equals(new \MongoRegex('/.*'.$search_text.'.*/i')))
						->addAnd($qb->expr()->field("path")->equals( new \MongoRegex('/'.$parent_path.'(.+[\|]+)+/') ))
						//->limit(20)
						->getQuery()
						->execute();

        } else {
            $children = array();
        }
        //Añadimos los padres al resultado.
        $result = $children->toArray();
		foreach($children->toArray() as $tag){
			$result= $this->getAllParents($tag,$result,$parent->getId());
		}
		//Ordenamos los resultados
		usort($result, function ($x, $y) {
			return strcasecmp($x->getCod(), $y->getCod());
		}
		);
		
        return $this->render(
			'PumukitNewAdminBundle:MultimediaObject:listtagsajax.html.twig',
			array('root' => $root, 'nodes' => $result, 'mmId' => $mmId, 'block_tag' => $parent->getId(), 'parent' => $parent ,'search_text' => $search_text )
        );
    }
   private function getAllParents($element, $tags = array(), $top_parent) {
		if($element->getParent()!=null) {
			$parentMissing = true;
			foreach($tags as $tag) {
				if($element->getParent() == $tag ) {
					$parentMissing=false; 
					break; }
			}
			
			if($parentMissing) {
				$parent= $element->getParent();//"retrieveByPKWithI18n");
				if($parent->getId()!=$top_parent){
					$tags[] = $parent;
					$tags = $this->getAllParents($parent, $tags, $top_parent);
				}
			}
		}
		return $tags;
	}
}
