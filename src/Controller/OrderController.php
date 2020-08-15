<?php
/**
 * Created by PhpStorm.
 * User: Muhammed Tepe
 * Date: 14/08/2020
 */

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class OrderController
 * @package App\Controller
 * @Route("/api", name="order_api")
 */
class OrderController extends AbstractController
{
    /**
     * @param OrderRepository $orderRepository
     * @return JsonResponse
     * @Route("/orders", name="orders", methods={"GET"})
     */
    public function getOrders(OrderRepository $orderRepository)
    {
        $data = $orderRepository->findAll();
        return $this->response($data);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param OrderRepository $orderRepository
     * @return JsonResponse
     * @throws \Exception
     * @Route("/orders", name="orders_add", methods={"POST"})
     */
    public function addOrder(Request $request, EntityManagerInterface $entityManager, OrderRepository $orderRepository)
    {

        try {
            $request = $this->transformJsonBody($request);

            if (
                !$request || !$request->get('orderCode')
                || !$request->request->get('productId')
                || !$request->request->get('quantity')
                || !$request->request->get('address')
                || !$request->request->get('shippingDate')
            ) {
                throw new \Exception();
            }

            $order = new Order();
            $order->setOrderCode($request->get('orderCode'));
            $order->setProductId($request->get('productId'));
            $order->setQuantity($request->get('quantity'));
            $order->setAddress($request->get('address'));
            $shippingDate = new \DateTime($request->get('shippingDate'));
            $order->setShippingDate($shippingDate);
            $entityManager->persist($order);
            $entityManager->flush();

            $data = [
                'status' => 200,
                'success' => "Your order has been created successfully",
            ];
            return $this->response($data);

        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'errors' => "Invalid or missing data",
            ];
            return $this->response($data, 422);
        }

    }

    /**
     * @param OrderRepository $orderRepository
     * @param $id
     * @return JsonResponse
     * @Route("/orders/{id}", name="orders_get", methods={"GET"})
     */
    public function getOrder(OrderRepository $orderRepository, $id){
        $order = $orderRepository->find($id);

        if (!$order){
            $data = [
                'status' => 404,
                'errors' => "Order not found",
            ];
            return $this->response($data, 404);
        }
        return $this->response($order);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param OrderRepository $orderRepository
     * @param $id
     * @return JsonResponse
     * @Route("/orders/{id}", name="orders_put", methods={"PUT"})
     */
    public function updatePost(Request $request, EntityManagerInterface $entityManager, OrderRepository $orderRepository, $id){

        try{
            $order = $orderRepository->find($id);

            if (!$order){
                $data = [
                    'status' => 404,
                    'errors' => "Order not found",
                ];
                return $this->response($data, 404);
            }

            $request = $this->transformJsonBody($request);

            if (
                !$request || !$request->get('orderCode')
                || !$request->request->get('productId')
                || !$request->request->get('quantity')
                || !$request->request->get('address')
                || !$request->request->get('shippingDate')
            ) {
                throw new \Exception();
            }


            $orderShippingDate = $order->getShippingDate()->format('Y-m-d');
            $today = date('Y-m-d', strtotime('now'));

            if ($orderShippingDate === $today) {
                $data = [
                    'status' => 403,
                    'errors' => "Orders can't be updated at shipping date.",
                ];
                return $this->response($data, 422);
            }


            $order->setOrderCode($request->get('orderCode'));
            $order->setProductId($request->get('productId'));
            $order->setQuantity($request->get('quantity'));
            $order->setAddress($request->get('address'));
            $shippingDate = new \DateTime($request->get('shippingDate'));
            $order->setShippingDate($shippingDate);
            $entityManager->flush();

            $data = [
                'status' => 200,
                'errors' => "Order updated successfully",
            ];
            return $this->response($data);

        } catch (\Exception $e){
            $data = [
                'status' => 422,
                'errors' => "Invalid or missing data",
            ];
            return $this->response($data, 422);
        }

    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param OrderRepository $orderRepository
     * @param $id
     * @return JsonResponse
     * @Route("/orders/{id}", name="orders_delete", methods={"DELETE"})
     */
    public function deleteOrder(EntityManagerInterface $entityManager, OrderRepository $orderRepository, $id){
        $order = $orderRepository->find($id);

        if (!$order){
            $data = [
                'status' => 404,
                'errors' => "Order not found",
            ];
            return $this->response($data, 404);
        }

        $entityManager->remove($order);
        $entityManager->flush();
        $data = [
            'status' => 200,
            'errors' => "Order removed successfully",
        ];
        return $this->response($data);
    }

    #region --> HELPER FUNCTIONS

    /**
     * Returns a JSON response
     *
     * @param array $data
     * @param $status
     * @param array $headers
     * @return JsonResponse
     */
    public function response($data, $status = 200, $headers = [])
    {
        return new JsonResponse($data, $status, $headers);
    }

    protected function transformJsonBody(\Symfony\Component\HttpFoundation\Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $request;
        }

        $request->request->replace($data);

        return $request;
    }

    #endregion
}