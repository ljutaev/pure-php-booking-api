<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Bus\CommandBus;
use App\Application\Command\BookRoom\BookRoomCommand;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\RoomNotAvailableException;
use App\Domain\ValueObject\BookingId;
use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\Request;
use App\Presentation\Http\RequestValidator;

final class BookingController
{
    public function __construct(private readonly CommandBus $bus)
    {
    }

    public function create(Request $request): JsonResponse
    {
        $errors = RequestValidator::validate($request->body, [
            'userId'   => 'required|string',
            'roomId'   => 'required|string',
            'checkIn'  => 'required|string',
            'checkOut' => 'required|string',
            'guests'   => 'required|int',
        ]);

        if ($errors !== null) {
            return JsonResponse::unprocessableEntity($errors);
        }

        $command = new BookRoomCommand(
            (string) $request->body['userId'],
            (string) $request->body['roomId'],
            (string) $request->body['checkIn'],
            (string) $request->body['checkOut'],
            (int) $request->body['guests'],
            isset($request->body['specialRequests']) ? (string) $request->body['specialRequests'] : null,
        );

        try {
            $bookingId = $this->bus->dispatch($command);
            assert($bookingId instanceof BookingId);
        } catch (EntityNotFoundException $e) {
            return JsonResponse::notFound($e->getMessage());
        } catch (RoomNotAvailableException $e) {
            return JsonResponse::conflict($e->getMessage());
        } catch (BusinessRuleViolationException $e) {
            return JsonResponse::unprocessableEntity(['_general' => $e->getMessage()]);
        }

        return JsonResponse::created([
            'id'     => $bookingId->value,
            '_links' => [
                'self' => ['href' => "/api/v1/bookings/{$bookingId->value}"],
            ],
        ]);
    }
}
