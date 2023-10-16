# Transactional MiniBus

## Transactional middleware

Make your MiniBus more safe by using a transactional middleware, so that either
all changes in a transaction are persisted, or all of them are rolled back.

e.g. imagine a scenario where `HandlerA` works OK, but `HandlerB` throws an
`Exception`. In such cases you probably want to rollback database changes
made by `HandlerA`. Then you could make both handlers run within
`TransactionalMiddleware`.

```mermaid
sequenceDiagram
    autonumber
    participant YourApp
    participant MiniBus
    participant MiddlewareStack
    participant TransactionalMiddleware
    participant Database
    YourApp->>MiniBus: dispatch(message)
    MiniBus->>MiddlewareStack: handle(message)
    MiddlewareStack->>TransactionalMiddleware: handle(message)
    TransactionalMiddleware->>Database: beginTransaction()
    loop next middleware 
        TransactionalMiddleware->>TransactionalMiddleware: handle(message)
        Note right of TransactionalMiddleware: something might go wrong here
    end
    TransactionalMiddleware->>Database: commit() or rollback()
    box transactional
        participant TransactionalMiddleware
        participant Database
    end
```

## Transactional transporter

Make your MiniBus compatible with the [transactional outbox pattern][1]:

> [...]  first store the message in the database as part of the transaction that
> updates the business entities. A separate process then sends the messages to the message broker.

A transactional transporter will persist the message into a database table,
using the same database connection as other services in your app.

Then a worker will fetch messages from the table and send them to the message
broker.

```mermaid
sequenceDiagram
    autonumber
    participant YourApp
    participant MiniBus
    participant MiddlewareStack
    participant TransactionalTransporter
    participant Database
    participant TransactionalWorker
    participant MessageBroker
    YourApp->>MiniBus: dispatch(message)
    MiniBus->>MiddlewareStack: handle(message)
    MiddlewareStack->>TransactionalTransporter: handle(message)
    TransactionalTransporter->>Database: persist(message)
    Database->>TransactionalWorker: fetchMessages()
    loop publish
        TransactionalWorker->>MessageBroker: dispatch(message)
    end
    box persist
        participant YourApp
        participant MiniBus
        participant MiddlewareStack
        participant TransactionalTransporter
        participant Database
    end
    box publish
        participant TransactionalWorker
        participant MessageBroker
    end
```

[1]: https://microservices.io/patterns/data/transactional-outbox.html
